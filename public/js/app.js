import { initBackend, loadBackendConfig, db, demoMode, setDemoMode } from './mysql.js';
import { getRestHours, restSecondsLeft, fmtCountdown, cdClass, getAllCrew, cts, initials, fmtTime, todayStr, fmtLastUpd, kpiHtml, dlCSV } from './helpers.js';
import { DEPOTS, DEPOT_COLORS, REST_HOURS, STATUS_META, STATUSES, getDesignationLabel, getDesignationOptions, getDesignationRegistry, isDesignationRestEligible, normalizeDesignation, normalizeDesignationKey, setDesignationRegistry, setDepotConfig, setStatusConfig, setTrainTypeConfig, setShiftConfig } from './constants.js';
import { collection, query, where, onSnapshot, getDocs, getDoc, doc, setDoc, deleteDoc, writeBatch, serverTimestamp } from './mysql.js';
/* ════════ CONSTANTS ════════════════════════════════════════════════════════ */
const HOME_REST_HOURS=12;
const AWAY_REST_HOURS=10;
const AVT_PAL=[['#E8F5E9','#1B5E20'],['#E3F2FD','#0D47A1'],['#FFF3E0','#E65100'],['#F3E5F5','#4A148C'],['#FFEBEE','#B71C1C'],['#E0F2F1','#00695C'],['#FFFDE7','#F57F17']];
const REPORT_TYPES=[
  {id:'status',label:'Daily status'},
  {id:'monthly',label:'Monthly register'},
  {id:'utilization',label:'Utilization'},
  {id:'absence',label:'Absence / NTB'},
  {id:'print',label:'Printable register'},
];
const DEFAULT_DESIGNATION_DEFINITIONS=[
  {id:'locomotive_driver',label:'Locomotive Driver',aliases:['driver','train_driver'],restEligible:true,canLogin:true,isCrewMember:true,isUser:false,order:10},
  {id:'guard',label:'Guard',aliases:['train_guard'],restEligible:true,canLogin:true,isCrewMember:true,isUser:false,order:20},
    {id:'shunter_driver',label:'Shunter Driver',aliases:['shunter','shunting'],restEligible:false,canLogin:true,isCrewMember:true,isUser:false,order:40},
  {id:'lio',label:'LIO',aliases:['loco_inspection_officer'],restEligible:false,canLogin:true,isCrewMember:true,isUser:false,order:45},
  {id:'inspector',label:'Inspector',aliases:['inspector'],restEligible:false,canLogin:true,isCrewMember:false,isUser:true,order:50},
  {id:'station_officer',label:'Station Officer',aliases:['station officer'],restEligible:false,canLogin:true,isCrewMember:false,isUser:true,order:60},
  {id:'booking_officer',label:'Booking Officer',aliases:['booking officer'],restEligible:false,canLogin:true,isCrewMember:false,isUser:true,order:70},
  {id:'hq_admin',label:'HQ Admin',aliases:['hq admin'],restEligible:false,canLogin:true,isCrewMember:false,isUser:true,order:80},
  {id:'super_admin',label:'Super Admin',aliases:['super admin'],restEligible:false,canLogin:true,isCrewMember:false,isUser:true,order:90},
];
let REPORT_TEMPLATES={};
// Default templates removed so the frontend relies on server-managed report definitions.
const DEFAULT_REPORT_TEMPLATES=[];
const USER_ROLE_OPTIONS=[
  {id:'super_admin',label:'Super Admin'},
  {id:'hq_admin',label:'HQ Admin'},
  {id:'station_officer',label:'Station Officer'},
  {id:'booking_officer',label:'Booking Officer'},
  {id:'crew_admin',label:'Crew Admin'},
];

const PAGE_PATHS = {
  dashboard: '/crew-dashboard',
  roster: '/crew-roster',
  rest: '/crew-rest',
  monthly: '/crew-monthly',
  reports: '/crew-reports',
};

// App ready flag for lazy-loaded page modules
window.__CREW_APP_READY = false;
function getPageFromPathname(pathname = window.location.pathname) {
  const normalized = String(pathname).toLowerCase().replace(/\/+$/g, '');
  return Object.entries(PAGE_PATHS).find(([, path]) => path === normalized)?.[0] || null;
}

// Safe DOM setters to avoid runtime errors when elements are missing
function safeSetInner(id, html) {
  try {
    const e = document.getElementById(id);
    if (e) e.innerHTML = html;
  } catch (err) {
    console.warn('safeSetInner failed for', id, err && err.message);
  }
}

function safeSetText(id, text) {
  try {
    const e = document.getElementById(id);
    if (e) e.textContent = text;
  } catch (err) {
    console.warn('safeSetText failed for', id, err && err.message);
  }
}

function selectMonthDay(day){
  const numericDay = Number(day);
  if (!Number.isInteger(numericDay) || numericDay < 1) return;
  selectedMonthDay = numericDay;
  renderMonthly();
}

function selectMonthlyCrewDay(depot,id,day){
  selectedMonthCrewKey = `${depot}::${id}`;
  selectMonthDay(day);
}

function selectMonthlyCrew(depot,id){
  selectedMonthCrewKey = `${depot}::${id}`;
  renderMonthly();
}

function buildMonthlyTotals(allCrew, maxDay){
  const totals = {BK:0,SB:0,R:0,L:0,SK:0,ABS:0,T:0,NTB:0,TO:0};
  allCrew.forEach(c=>{
    for(let d=1;d<=maxDay;d++){
      const code = getFinalStatusForDay(c,d) || '';
      if (totals[code] !== undefined) totals[code]++;
    }
  });
  return totals;
}

function buildDayTotals(allCrew, day){
  const totals = {BK:0,SB:0,R:0,L:0,SK:0,ABS:0,T:0,NTB:0,TO:0};
  allCrew.forEach(c=>{
    const code = getFinalStatusForDay(c,day) || '';
    if (totals[code] !== undefined) totals[code]++;
  });
  return totals;
}

function getRoleSelectOptions(selectedRole){
  const roleSource = roleMetadataCache.length ? sortAccessMetaRecords(roleMetadataCache) : USER_ROLE_OPTIONS;
  return roleSource.map(role=>{
    const id = role.id || role.value || '';
    const label = role.label || role.name || id;
    return `<option value="${id}"${id===selectedRole?' selected':''}>${label}</option>`;
  }).join('');
}

function getDepotSelectOptions(selectedDepot='HQ'){
  const depotSource = getAllDepotMetadata().length
    ? getAllDepotMetadata().filter(depot => depot.active !== false)
    : [{id:'HQ',label:'HQ'}, ...getActiveDepots().map(depot=>({id:depot,label:depot}))];
  const seen = new Set();
  return depotSource.filter(depot=>{
    const id = String(depot.id || '').trim();
    if(!id || seen.has(id)) return false;
    seen.add(id);
    return true;
  }).map(depot=>{
    const id = String(depot.id || '').trim();
    const label = String(depot.label || id).trim();
    return `<option value="${id}"${id===selectedDepot?' selected':''}>${label}</option>`;
  }).join('');
}

function getPermissionSelectOptions(selectedPermissions=[]){
  const selected = new Set((Array.isArray(selectedPermissions) ? selectedPermissions : String(selectedPermissions||'').split(',')).map(item=>String(item).trim()).filter(Boolean));
  return sortAccessMetaRecords(permissionMetadataCache).map(permission=>{
    const id = permission.id || '';
    const label = permission.label || id;
    return `<option value="${id}"${selected.has(id)?' selected':''}>${label}</option>`;
  }).join('');
}

async function seedUsersIfEmpty(){
  // No static user account seeding; user records must exist in MySQL.
  return;
}

async function ensureCoreAccessUsers(){
  // No static user fallback registration when using dynamic MySQL data.
  return;
}

/* ════════ DATE ════════════════════════════════════════════════════════════ */
const TODAY=new Date();
const CY=TODAY.getFullYear(),CM=TODAY.getMonth(),CD=TODAY.getDate();
const DAYS_IN_MON=new Date(CY,CM+1,0).getDate();
const MONTH_NAME=TODAY.toLocaleString('en-KE',{month:'long',year:'numeric'});
const DAY_NAMES=['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
const MONTH_KEY=`${CY}-${String(CM+1).padStart(2,'0')}`;
const DEFAULT_SHIFT_LABEL='Day shift';

/* ════════ STATE ════════════════════════════════════════════════════════════ */
let currentUser=null;
let state={};        // {depot:{crewId:crewDoc}}
let listeners=[];
let currentPage='monthly',activeFilter='all',hqDepotView='all',editKey=null;
let selectedMonthDay = new Date().getDate();
let selectedMonthCrewKey = null;
let cdInterval=null; // countdown ticker
let currentModalGrade=null;
let currentModalInRoom=false;
let crewDetailsKey=null;
let crewDetailsTimer=null;
let lastRefreshTime=0; // prevent rapid refresh spamming
const MIN_REFRESH_INTERVAL=2000; // minimum milliseconds between refreshes
let depotMetadataCache={};
let designationMetadataCache={};
let statusMetadataCache={};
let userMetadataCache={};
let roleMetadataCache=[];
let permissionMetadataCache=[];
let trainTypeMetadataCache=[];
let shiftMetadataCache=[];
let adminSectionView='overview';
const SESSION_KEY='KR_Crew_Session';

const SIMPLE_META_CONFIG={
  trainType:{collection:'trainTypeMeta',title:'Train types',note:'Train types are used in the booking modal and crew table.'},
  shift:{collection:'shiftMeta',title:'Shifts',note:'Shifts populate the crew modal and crew records.'},
};

const ACCESS_META_CONFIG={
  roles:{collection:'roles',title:'Roles',note:'Roles define user access levels for the system.'},
  permissions:{collection:'permissions',title:'Permissions',note:'Permissions are assigned to users and roles as reusable access flags.'},
};

function setHqDepotView(value){
  hqDepotView=value;
  if(currentPage==='roster') renderRoster();
  if(currentPage==='monthly') renderMonthly();
}

function setAdminSectionView(value){
  adminSectionView=value;
  if(currentPage==='admin') renderAdmin();
}

function updateSidebarSections(){
  const depotSection=document.getElementById('depotSection');
  const adminSection=document.getElementById('adminSection');
  if(depotSection) depotSection.style.display=currentUser?.isHQ?'block':'none';
  if(adminSection) adminSection.style.display=hasGlobalAccess()?'block':'none';
}

function getActiveDepots(){
  return DEPOTS;
}

function getAllDepotMetadata(){
  return Object.values(depotMetadataCache).sort((a,b)=>(a.order||999)-(b.order||999)||String(a.label||a.id).localeCompare(String(b.label||b.id)));
}

function normalizeCrewDepot(depot){
  const value=String(depot||'').trim();
  if(!value)return value;
  const lower=value.toLowerCase();
  const match=getAllDepotMetadata().find(item=>
    String(item.id||'').toLowerCase()===lower || String(item.label||'').toLowerCase()===lower
  );
  return match?.id||value;
}

function getAllDesignationMetadata(){
  return Object.values(designationMetadataCache).sort((a,b)=>(a.order||999)-(b.order||999)||String(a.label||a.id).localeCompare(String(b.label||b.id)));
}

function getAllStatusMetadata(){
  return Object.values(statusMetadataCache).sort((a,b)=>(a.order||999)-(b.order||999)||String(a.label||a.id).localeCompare(String(b.label||b.id)));
}

function setDepotMetadata(records){
  depotMetadataCache={};
  records.forEach(record=>{ if(record && record.id) depotMetadataCache[record.id] = record; });
}

function setDesignationMetadata(records){
  designationMetadataCache={};
  records.forEach(record=>{ if(record && record.id) designationMetadataCache[record.id] = record; });
}

function setStatusMetadata(records){
  statusMetadataCache={};
  records.forEach(record=>{ if(record && record.id) statusMetadataCache[record.id] = record; });
}

function setUserMetadata(records){
  userMetadataCache = records || [];
}

function setRoleMetadata(records){
  roleMetadataCache = records || [];
}

function setPermissionMetadata(records){
  permissionMetadataCache = records || [];
}

function normalizeSimpleMetaRecord(docSnapOrData){
  const data = docSnapOrData?.data ? docSnapOrData.data() : docSnapOrData;
  const id = String(data?.id || docSnapOrData?.id || docSnapOrData?.record_id || '').trim();
  if(!id) return null;
  return {
    id,
    label:String(data?.label||id).trim()||id,
    order:Number.isFinite(Number(data?.order)) ? Number(data.order) : 999,
    active:data?.active !== false,
  };
}

function sortSimpleMetaRecords(records){
  return (records || []).filter(Boolean).sort((a,b)=>(a.order||999)-(b.order||999)||String(a.label||a.id).localeCompare(String(b.label||b.id)));
}

function getSimpleMetaConfig(key){
  return SIMPLE_META_CONFIG[key];
}

function getAccessMetaConfig(key){
  return ACCESS_META_CONFIG[key];
}

function getActiveSimpleMetaRecords(key){
  const source = key==='trainType' ? trainTypeMetadataCache : shiftMetadataCache;
  return sortSimpleMetaRecords(source.filter(item=>item.active !== false));
}

function setSimpleMetaRecords(key, records){
  const next = sortSimpleMetaRecords(records);
  if(key==='trainType'){
    trainTypeMetadataCache = next;
    setTrainTypeConfig(next.map(item=>item.label));
  } else if(key==='shift'){
    shiftMetadataCache = next;
    setShiftConfig(next.map(item=>item.label));
  }
}

function getSimpleMetaCollection(key){
  return getSimpleMetaConfig(key)?.collection || '';
}

function getAccessMetaCollection(key){
  return getAccessMetaConfig(key)?.collection || '';
}

function buildSimpleMetaOptions(key, selected=''){
  const records = getActiveSimpleMetaRecords(key);
  return records.map(record=>`<option value="${record.label}"${record.label===selected?' selected':''}>${record.label}</option>`).join('');
}

function populateTrainTypeSelect(selected=''){
  const trainTypeSelect = document.getElementById('mTrainType');
  if(trainTypeSelect) trainTypeSelect.innerHTML = buildSimpleMetaOptions('trainType', selected || getActiveSimpleMetaRecords('trainType')[0]?.label || '');
}

function buildShiftOptions(selected=''){
  const defaultShift = getActiveSimpleMetaRecords('shift')[0]?.label || '';
  return buildSimpleMetaOptions('shift', selected || defaultShift);
}

function populateShiftSelects(selected=''){
  const shiftSelect=document.getElementById('mShift');
  if(shiftSelect) shiftSelect.innerHTML = buildShiftOptions(selected);
  const addShiftSelect=document.getElementById('addShift');
  if(addShiftSelect) addShiftSelect.innerHTML = buildShiftOptions(selected);
}

function getTrainTypeBadgeStyle(trainType){
  const value = String(trainType || '').trim();
  const styles = {
    Freight:'background:var(--freight-lt);color:var(--freight);border:1px solid #FFE082;font-size:10px;padding:1px 6px;border-radius:10px;font-weight:600',
    Commuter:'background:var(--commuter-lt);color:var(--commuter);border:1px solid #90CAF9;font-size:10px;padding:1px 6px;border-radius:10px;font-weight:600',
    Passenger:'background:var(--passenger-lt);color:var(--passenger);border:1px solid #A5D6A7;font-size:10px;padding:1px 6px;border-radius:10px;font-weight:600',
    Engineering:'background:var(--engineering-lt);color:var(--engineering);border:1px solid #CE93D8;font-size:10px;padding:1px 6px;border-radius:10px;font-weight:600',
    Shunting:'background:var(--shunting-lt);color:var(--shunting);border:1px solid #BCAAA4;font-size:10px;padding:1px 6px;border-radius:10px;font-weight:600',
  };
  if(styles[value]) return styles[value];
  if(!value) return 'color:var(--text3);font-size:11px';
  return 'background:#ECEFF1;color:#37474F;border:1px solid #CFD8DC;font-size:10px;padding:1px 6px;border-radius:10px;font-weight:600';
}

function getTrainTypeLabel(trainType){
  return String(trainType || '-');
}

function getCrewShiftLabel(crew){
  const shift = String(crew?.shift || '').trim();
  return shift || DEFAULT_SHIFT_LABEL;
}

function getCrewStaffNumberLabel(crew){
  return String(crew?.staff_number || '').trim() || '-';
}

function getCsrfToken(){
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

async function fetchLocalAdminMeta(){
  const resp = await fetch('/admin/meta', {cache:'no-store'});
  if(!resp.ok){
    throw new Error(`Local admin meta fetch failed (${resp.status})`);
  }
  return resp.json();
}

async function saveLocalAdminRecord(collection,id,payload){
  const resp = await fetch(`/admin/meta/${encodeURIComponent(collection)}/${encodeURIComponent(id)}`, {
    method:'POST',
    headers:{'Content-Type':'application/json','X-CSRF-TOKEN':getCsrfToken()},
    body: JSON.stringify(payload),
  });
  if(!resp.ok){
    const text = await resp.text();
    throw new Error(text || `Local admin save failed (${resp.status})`);
  }
  return resp.json();
}

async function deleteLocalAdminRecord(collection,id){
  const resp = await fetch(`/admin/meta/${encodeURIComponent(collection)}/${encodeURIComponent(id)}`, {
    method:'DELETE',
    headers:{'X-CSRF-TOKEN':getCsrfToken()},
  });
  if(!resp.ok){
    const text = await resp.text();
    throw new Error(text || `Local admin delete failed (${resp.status})`);
  }
  return resp.json();
}

function hasGlobalAccess(){
  return !!currentUser && (currentUser.isHQ || currentUser.isSuperAdmin);
}

function canManageCrew(depot){
  return hasGlobalAccess() || String(currentUser?.depot||'').toLowerCase()===String(depot||'').toLowerCase();
}

function getTopAccessLabel(){
  if(currentUser?.isSuperAdmin) return 'Super Admin - All Depots';
  if(currentUser?.isHQ) return 'HQ - All Depots';
  return `${currentUser?.depot||''} Depot`;
}

function isRestAllowedForGrade(grade){
  return isDesignationRestEligible(grade);
}

function updateStatusValidation(){
  const grade=currentModalGrade;
  const statusEl=document.getElementById('mStatus');
  const hintEl=document.getElementById('statusHint');
  const saveBtn=document.getElementById('mSaveBtn');
  const grid=document.getElementById('mStatusGrid');
  const rBtn=grid?grid.querySelector('.st-pick[data-status="R"]'):null;
  if(!grade){
    if(rBtn)rBtn.classList.remove('disabled');
    if(hintEl)hintEl.style.display='none';
    if(saveBtn)saveBtn.disabled=false;
    return;
  }
  const allowed=isRestAllowedForGrade(grade);
  if(rBtn)rBtn.classList.toggle('disabled',!allowed);
  if(statusEl.value==='R' && !allowed){
    if(hintEl){
      hintEl.textContent='This designation does not qualify for Resting. Use Stand By, Booked, shift work, or another status.';
      hintEl.style.display='block';
    }
    if(saveBtn)saveBtn.disabled=true;
  } else {
    if(hintEl)hintEl.style.display='none';
    if(saveBtn)saveBtn.disabled=false;
  }
}

function applyInRoomStatusLock(){
  const statusEl=document.getElementById('mStatus');
  const grid=document.getElementById('mStatusGrid');
  const hintEl=document.getElementById('statusHint');
  const saveBtn=document.getElementById('mSaveBtn');
  if(!currentModalInRoom){
    if(grid) grid.querySelectorAll('.st-pick').forEach(el=>el.classList.remove('disabled'));
    if(hintEl)hintEl.style.display='none';
    if(saveBtn)saveBtn.disabled=false;
    return;
  }
  // A crew member checked into a running room may only keep the Resting (R)
  // status. Every other status is grayed out until they are checked out.
  if(grid) grid.querySelectorAll('.st-pick').forEach(el=>{
    const locked=el.dataset.status!=='R';
    el.classList.toggle('disabled',locked);
  });
  if(statusEl){
    statusEl.value='R';
    updateStatusPicker();
  }
  if(hintEl){
    hintEl.textContent='Checked into a running room — status is locked to Resting. Check the crew out of the room before changing status.';
    hintEl.style.display='block';
  }
  if(saveBtn)saveBtn.disabled=false;
}

function getServerUser(){
  return window.__CURRENT_USER__ || null;
}

function normalizeCurrentUser(user){
  if(!user) return null;
  return {
    username: user.username || user.email || '',
    depot: user.depot || user.depot_code || 'HQ',
    name: user.name || user.username || '',
    isHQ: !!user.isHQ,
    isSuperAdmin: !!user.isSuperAdmin,
    role: user.role || user.role_code || '',
  };
}

function setCurrentUser(user){
  const normalized = normalizeCurrentUser(user);
  if(!normalized) return;
  currentUser = normalized;
  window.currentUser = currentUser;
  const tbBadgeEl = document.getElementById('tbBadge');
  if(tbBadgeEl) tbBadgeEl.textContent = getTopAccessLabel();
  const tbUserEl = document.getElementById('tbUser');
  if(tbUserEl) tbUserEl.textContent = currentUser.name || currentUser.username || '';
  updateSidebarSections();
  if(currentUser.isHQ){
    const sbDepotsEl = document.getElementById('sbDepots');
    if(sbDepotsEl){
      sbDepotsEl.innerHTML = getActiveDepots().map(d=>`<div class="sb-depot" onclick="setHqDepotView('${d}');goPage('roster')" id="sbd-${d}"><div class="sb-depot-dot" style="background:${DEPOT_COLORS[d]}"></div>${d}</div>`).join('');
    }
  }
}

function persistSession(){
  if(!currentUser){localStorage.removeItem(SESSION_KEY);return;}
  localStorage.setItem(SESSION_KEY,JSON.stringify({
    username:currentUser.username,
    depot:currentUser.depot,
    name:currentUser.name,
    isHQ:currentUser.isHQ,
    isSuperAdmin:currentUser.isSuperAdmin,
    role:currentUser.role,
    hqDepotView: currentUser.isHQ ? hqDepotView : 'all',
    currentPage
  }));
}

async function restoreSession(){
  const raw = localStorage.getItem(SESSION_KEY);
  let saved = null;
  if(raw){
    try{
      saved = JSON.parse(raw);
    }catch(err){
      console.warn('Session parse failed, falling back to server user',err);
      saved = null;
    }
  }
  // Laravel authentication is authoritative. Local storage only retains UI
  // preferences, never the identity or permissions of a previous user.
  const serverUser = getServerUser();
  if(!serverUser) return false;
  saved = {
    username: serverUser.username,
    depot: serverUser.depot,
    name: serverUser.name,
    isHQ: serverUser.isHQ,
    isSuperAdmin: serverUser.isSuperAdmin,
    role: serverUser.role,
    hqDepotView: saved?.hqDepotView || 'all',
    currentPage: saved?.currentPage || 'monthly',
  };
  const normalized = normalizeCurrentUser(saved);
  if(!normalized) return false;
  currentUser = normalized;
  hqDepotView = currentUser.isHQ ? saved.hqDepotView || 'all' : 'all';
  const pageFromPath = getPageFromPathname(window.location.pathname);
  currentPage = pageFromPath || saved.currentPage || 'monthly';
  const loginPageEl = document.getElementById('loginPage'); if (loginPageEl) loginPageEl.classList.remove('show');
  const appEl = document.getElementById('app'); if (appEl) appEl.classList.add('show');
  setCurrentUser(currentUser);
  const lds = hasGlobalAccess()?getActiveDepots():[currentUser.depot];
  if(db){
    await seedDepotMetaIfEmpty();
    await loadDepotMeta();
    await seedDesignationMetaIfEmpty();
    await loadDesignationMeta();
    await seedUsersIfEmpty();
    await seedStatusMetaIfEmpty();
    await loadStatusMeta();
    await seedTrainTypeMetaIfEmpty();
    await loadTrainTypeMeta();
    await seedShiftMetaIfEmpty();
    await loadShiftMeta();
    await loadAccessMeta();
    populateDesignationSelect();
    populateTrainTypeSelect();
    populateShiftSelects();
    await Promise.all(lds.map(seedDepotIfEmpty));
    await ensureRestCountdownSample();
    attachListeners(lds);
  }
  setInterval(updateClock,1000);
  updateClock();
  if(cdInterval)clearInterval(cdInterval);
  goPage(currentPage);
  cdInterval=setInterval(()=>{checkRestExpirations();if(currentPage==='rest')renderRest();else updateCountdownsInTable();},10000);
  setLog(`Restored session - ${currentUser.name}`);
  return true;
}

/* ════════ REST COUNTDOWN ══════════════════════════════════════════════════ */

// Auto-promote resting drivers to Standby when countdown expires
async function checkRestExpirations(){
  for(const depot of getActiveDepots()){
    const crew=Object.values(state[depot]||{});
    for(const c of crew){
      if(c.status==='R'&&isDesignationRestEligible(c.grade)){
        // While a crew member is actually checked in to a running room, rest only
        // ends at checkout (handled by the register) so the officer can still see
        // the COMPLETE countdown and plan around the room stay.
        if(c.rrRoomId && c.rrCheckedOut===false) continue;
        const sec=restSecondsLeft(c);
        if(sec!==null&&sec<=0){
          const hours = getRestHours(c);
          const now = new Date();
          const monthly={...(c.monthly||{})};monthly[`d${CD}`]='SB';
          const status_segments = buildStatusSegmentsForDay({...c,status:'R'}, CD, 'SB', {
            now,
            note:`Auto-promoted from Resting after ${hours}h`,
          });
          const updates={status:'SB',since:fmtTime(now),restStarted:null,updatedBy:'Auto-system',notes:`Auto-promoted from Resting after ${hours}h`,monthly,status_segments};
          await writeCrewDoc(depot,c.id,updates);
          setLog(`${c.name} (${depot}) rest period complete - auto-promoted to Standby`);
        }
      }
    }
  }
}

async function seedDepotIfEmpty(depot){
  // No local crew seeding. Crew documents must be supplied from MySQL.
  return;
}

function normalizeDepotMetaRecord(docSnapOrData){
  const data = docSnapOrData?.data ? docSnapOrData.data() : docSnapOrData;
  const id = data?.id || (docSnapOrData?.id||docSnapOrData?.record_id);
  if(!id) return null;
  return {
    id,
    label:String(data.label||id),
    color:String(data.color||DEPOT_COLORS[id]||'#37474F'),
    restHours:typeof data.restHours==='number'?data.restHours:(REST_HOURS[id]||12),
    order:typeof data.order==='number'?data.order:999,
    active:data.active!==false,
  };
}

async function seedDepotMetaIfEmpty(){
  // No static depot metadata seeding; admin-managed MySQL data is required.
  return;
}

async function loadDepotMeta(){
  if(!db){
    return await loadLocalDepotMeta();
  }
  try{
    const snap = await getDocs(collection(db,'depotMeta'));
    if(snap.empty){
      setDepotConfig([], {}, {});
      setDepotMetadata([]);
      return;
    }
    const allRecords=[];
    const activeRecords=[];
    const colors={};
    const hours={};
    snap.forEach(docSnap=>{
      const meta=normalizeDepotMetaRecord(docSnap);
      if(!meta) return;
      allRecords.push(meta);
      if(meta.active) activeRecords.push(meta);
      colors[meta.id]=meta.color;
      hours[meta.id]=meta.restHours;
    });
    allRecords.sort((a,b)=>(a.order||999)-(b.order||999)||String(a.label||a.id).localeCompare(String(b.label||b.id)));
    activeRecords.sort((a,b)=>(a.order||999)-(b.order||999)||String(a.label||a.id).localeCompare(String(b.label||b.id)));
    setDepotMetadata(allRecords);
    setDepotConfig(activeRecords.map(meta=>meta.id), colors, hours);
  }catch(err){
    console.error('Failed to load depot metadata',err);
    setDepotConfig([], {}, {});
    setDepotMetadata([]);
  }
}

async function loadLocalDepotMeta(){
  try{
    const data = await fetchLocalAdminMeta();
    const records = (data.depotMeta||[]).map(normalizeDepotMetaRecord).filter(Boolean);
    const colors={};
    const hours={};
    const activeRecords = [];
    records.forEach(meta=>{
      colors[meta.id]=meta.color;
      hours[meta.id]=meta.restHours;
      if(meta.active) activeRecords.push(meta);
    });
    activeRecords.sort((a,b)=>(a.order||999)-(b.order||999)||String(a.label||a.id).localeCompare(String(b.label||b.id)));
    records.sort((a,b)=>(a.order||999)-(b.order||999)||String(a.label||a.id).localeCompare(String(b.label||b.id)));
    setDepotMetadata(records);
    setDepotConfig(activeRecords.map(meta=>meta.id), colors, hours);
  }catch(err){
    console.error('Failed to load local depot metadata',err);
    setDepotConfig([], {}, {});
    setDepotMetadata([]);
  }
}

async function seedStatusMetaIfEmpty(){
  const defaults = [
    { id: 'BK', label: 'Booked', bg: '#E8F5E9', fg: '#1B5E20', order: 100, active: true },
    { id: 'SB', label: 'Stand By', bg: '#E3F2FD', fg: '#0D47A1', order: 200, active: true },
    { id: 'R', label: 'Resting', bg: '#F3F5FF', fg: '#4A148C', order: 300, active: true },
    { id: 'L', label: 'Leave', bg: '#FFF3E0', fg: '#E65100', order: 400, active: true },
    { id: 'SK', label: 'Sick', bg: '#FFEBEE', fg: '#B71C1C', order: 500, active: true },
    { id: 'ABS', label: 'Absent', bg: '#FEF2F2', fg: '#991B1B', order: 550, active: true },
    { id: 'T', label: 'Training', bg: '#E0F2F1', fg: '#00695C', order: 600, active: true },
    { id: 'NTB', label: 'NTB', bg: '#ECEFF1', fg: '#37474F', order: 700, active: true },
    { id: 'TO', label: 'Trip Off', bg: '#FCE4EC', fg: '#AD1457', order: 800, active: true },
  ];

  const seedStatus = async (record) => {
    if (!db) {
      await saveLocalAdminRecord('statusMeta', record.id, record);
      return;
    }
    await setDoc(doc(db, 'statusMeta', record.id), record, { merge: true });
  };

  if (!db) {
    try {
      const data = await fetchLocalAdminMeta();
      const existing = new Set((data.statusMeta || []).map(item => String(item.id || item.record_id || '').trim()).filter(Boolean));
      const missing = defaults.filter(status => !existing.has(status.id));
      if (!missing.length) return;
      await Promise.all(missing.map(seedStatus));
    } catch (err) {
      console.error('Failed to seed local status metadata', err);
    }
    return;
  }

  try {
    const snap = await getDocs(collection(db, 'statusMeta'));
    const existing = new Set();
    snap.forEach(docSnap => {
      const data = docSnap.data();
      const id = String(data?.id || docSnap.id || data?.record_id || '').trim();
      if (id) existing.add(id);
    });
    const missing = defaults.filter(status => !existing.has(status.id));
    if (!missing.length) return;
    await Promise.all(missing.map(seedStatus));
  } catch (err) {
    console.error('Failed to seed status metadata', err);
  }
}

async function loadStatusMeta(){
  if(!db){
    return await loadLocalStatusMeta();
  }
  try{
    const snap = await getDocs(query(collection(db,'statusMeta')));
    if(snap.empty){
      setStatusConfig([], {});
      setStatusMetadata([]);
      return;
    }
    const meta={};
    const order=[];
    snap.forEach(docSnap=>{
      const data = docSnap.data();
      const id=data.id||docSnap.id;
      if(!id) return;
      meta[id]={label:data.label||id,bg:data.bg||'#ECEFF1',fg:data.fg||'#37474F'};
      order.push({id,order:typeof data.order==='number'?data.order:999});
    });
    order.sort((a,b)=>a.order-b.order);
    setStatusConfig(order.map(x=>x.id), meta);
    setStatusMetadata(order.map(x=>({id:x.id,label:meta[x.id].label,bg:meta[x.id].bg,fg:meta[x.id].fg,order:x.order})));
  }catch(err){
    console.error('Failed to load status metadata',err);
    setStatusConfig([], {});
    setStatusMetadata([]);
  }
}

function normalizeStatusMetaRecord(docSnapOrData){
  const data = docSnapOrData?.data ? docSnapOrData.data() : docSnapOrData;
  const id = data?.id || docSnapOrData?.id || docSnapOrData?.record_id;
  if(!id) return null;
  return {
    id,
    label:String(data.label||id),
    bg:String(data.bg||'#ECEFF1'),
    fg:String(data.fg||'#37474F'),
    order:typeof data.order==='number'?data.order:999,
  };
}

async function loadLocalStatusMeta(){
  try{
    const data = await fetchLocalAdminMeta();
    const records = (data.statusMeta||[]).map(normalizeStatusMetaRecord).filter(Boolean);
    const meta={};
    const order=[];
    records.forEach(rec=>{
      meta[rec.id] = {label:rec.label,bg:rec.bg,fg:rec.fg};
      order.push({id:rec.id,order:rec.order||999});
    });
    order.sort((a,b)=>a.order-b.order);
    setStatusConfig(order.map(x=>x.id), meta);
    setStatusMetadata(records.sort((a,b)=> (a.order||999)-(b.order||999)||String(a.label||a.id).localeCompare(String(b.label||b.id))));
  }catch(err){
    console.error('Failed to load local status metadata',err);
    setStatusConfig([], {});
    setStatusMetadata([]);
  }
}

async function seedSimpleMetaIfEmpty(key, defaults){
  const collectionName = getSimpleMetaCollection(key);
  if(!collectionName) return;
  if(!db){
    try{
      const data = await fetchLocalAdminMeta();
      if((data[collectionName]||[]).length) return;
      await Promise.all(defaults.map((value, index)=>saveLocalAdminRecord(collectionName, value, {id:value,label:value,order:(index + 1) * 100,active:true})));
      return;
    }catch(err){
      console.error(`Failed to seed local ${collectionName}`, err);
      return;
    }
  }
  try{
    const snap = await getDocs(collection(db, collectionName));
    if(!snap.empty) return;
    await Promise.all(defaults.map((value, index)=>setDoc(doc(db, collectionName, value), {id:value,label:value,order:(index + 1) * 100,active:true})));
  }catch(err){
    console.error(`Failed to seed ${collectionName}`, err);
  }
}

async function loadSimpleMeta(key){
  const collectionName = getSimpleMetaCollection(key);
  if(!collectionName) return;
  if(!db){
    return await loadLocalSimpleMeta(key);
  }
  try{
    const snap = await getDocs(collection(db, collectionName));
    const records=[];
    snap.forEach(docSnap=>{
      const meta = normalizeSimpleMetaRecord(docSnap);
      if(meta) records.push(meta);
    });
    setSimpleMetaRecords(key, records);
  }catch(err){
    console.error(`Failed to load ${collectionName}`, err);
    setSimpleMetaRecords(key, []);
  }
}

async function loadLocalSimpleMeta(key){
  const collectionName = getSimpleMetaCollection(key);
  if(!collectionName) return;
  try{
    const data = await fetchLocalAdminMeta();
    const records = (data[collectionName]||[]).map(normalizeSimpleMetaRecord).filter(Boolean);
    setSimpleMetaRecords(key, records);
  }catch(err){
    console.error(`Failed to load local ${collectionName}`, err);
    setSimpleMetaRecords(key, []);
  }
}

async function seedTrainTypeMetaIfEmpty(){
  return;
}

async function loadTrainTypeMeta(){
  await loadSimpleMeta('trainType');
}

async function seedShiftMetaIfEmpty(){
  return;
}

async function loadShiftMeta(){
  await loadSimpleMeta('shift');
}

function normalizeDesignationMetaRecord(docSnapOrData){
  const data = docSnapOrData?.data ? docSnapOrData.data() : docSnapOrData;
  const id = data?.id || docSnapOrData?.id || docSnapOrData?.record_id;
  if(!id) return null;
  return {
    id,
    label:String(data.label||id),
    aliases:Array.isArray(data.aliases)?data.aliases:data.aliases?String(data.aliases).split(',').map(v=>v.trim()).filter(Boolean):[],
    restEligible:data.restEligible!==false,
    runningRoomEligible:data.runningRoomEligible!==undefined?data.runningRoomEligible!==false:data.restEligible!==false,
    canLogin:data.canLogin!==false,
    isCrewMember:!!data.isCrewMember,
    isUser:!!data.isUser,
    order:typeof data.order==='number'?data.order:999,
  };
}

async function seedDesignationMetaIfEmpty(){
  const seedDesignation = async (record) => {
    if(!db){
      await saveLocalAdminRecord('designationMeta', record.id, record);
      return;
    }
    await setDoc(doc(db,'designationMeta',record.id), record, {merge:true});
  };

  try{
    if(!db){
      const data = await fetchLocalAdminMeta();
      const existing = new Set((data.designationMeta||[]).map(item=>String(item.id||item.record_id||'').trim()).filter(Boolean));
      const missing = DEFAULT_DESIGNATION_DEFINITIONS.filter(def=>!existing.has(def.id));
      if(!missing.length) return;
      await Promise.all(missing.map(seedDesignation));
      return;
    }

    const snap = await getDocs(collection(db,'designationMeta'));
    const existing = new Set();
    snap.forEach(docSnap=>{
      const data=docSnap.data();
      const id=String(data?.id||docSnap.id||data?.record_id||'').trim();
      if(id) existing.add(id);
    });
    const missing = DEFAULT_DESIGNATION_DEFINITIONS.filter(def=>!existing.has(def.id));
    if(!missing.length) return;
    await Promise.all(missing.map(seedDesignation));
  }catch(err){
    console.error('Failed to seed designation metadata', err);
  }
}

async function loadDesignationMeta(){
  if(!db){
    return await loadLocalDesignationMeta();
  }
  try{
    const snap = await getDocs(collection(db,'designationMeta'));
    if(snap.empty){
      setDesignationRegistry({});
      setDesignationMetadata([]);
      return;
    }
    const registry={};
    const records=[];
    snap.forEach(docSnap=>{
      const meta=normalizeDesignationMetaRecord(docSnap);
      if(meta){ registry[meta.id]=meta; records.push(meta);}   
    });
    setDesignationRegistry(registry);
    setDesignationMetadata(records);
  }catch(err){
    console.error('Failed to load designation metadata',err);
    setDesignationRegistry({});
    setDesignationMetadata([]);
  }
}

async function loadLocalDesignationMeta(){
  try{
    const data = await fetchLocalAdminMeta();
    const records = (data.designationMeta||[]).map(normalizeDesignationMetaRecord).filter(Boolean);
    const registry={};
    records.forEach(meta => registry[meta.id]=meta);
    setDesignationRegistry(registry);
    setDesignationMetadata(records);
  }catch(err){
    console.error('Failed to load local designation metadata',err);
    setDesignationRegistry({});
    setDesignationMetadata([]);
  }
}

function buildStatusOptions(selected='SB'){
  const statusCodes = Array.isArray(STATUSES) && STATUSES.length
    ? STATUSES.slice()
    : Object.keys(STATUS_META).length
      ? Object.keys(STATUS_META).sort((a,b)=>{
          const aOrder = STATUS_META[a]?.order ?? 999;
          const bOrder = STATUS_META[b]?.order ?? 999;
          return aOrder - bOrder || String(a).localeCompare(String(b));
        })
      : ['BK','SB','R','L','SK','ABS','T','NTB','TO'];

  return statusCodes.map(id=>{
    const meta = STATUS_META[id] || { label: id };
    const label = id === 'SB' && (!meta.label || meta.label.toLowerCase() === 'standby') ? 'Stand By' : meta.label;
    return `<option value="${id}"${id===selected?' selected':''}>${id} - ${label}</option>`;
  }).join('');
}

function getStatusCodes(){
  // Prefer configured STATUSES (ordered), fall back to metadata or legacy ordered list.
  if (Array.isArray(STATUSES) && STATUSES.length) return STATUSES.slice();
  if (Object.keys(STATUS_META).length) {
    return Object.keys(STATUS_META).sort((a,b)=>{
      const aOrder = STATUS_META[a]?.order ?? 999;
      const bOrder = STATUS_META[b]?.order ?? 999;
      return aOrder - bOrder || String(a).localeCompare(String(b));
    });
  }
  return ['BK','SB','R','L','SK','ABS','T','NTB','TO'];
}

function getStatusMeta(code){
  return STATUS_META && STATUS_META[code] ? STATUS_META[code] : { label: code, bg: '#ECEFF1', fg: '#37474F' };
}

function getAbsenceStatusCodes(){
  // Dynamically derive absence-like statuses from metadata flag `isAbsence` if present.
  const codes = getStatusCodes();
  const fromMeta = codes.filter(c => STATUS_META[c] && STATUS_META[c].isAbsence === true);
  if(fromMeta.length) return fromMeta;
  // Fallback to historical absence codes
  return codes.filter(c => ['SK','L','ABS','NTB'].includes(c));
}

function populateStatusSelects(selected='SB'){
  const statusSelect=document.getElementById('mStatus');
  const addStatusSelect=document.getElementById('addStatus');
  if(statusSelect) statusSelect.innerHTML = buildStatusOptions(selected);
  if(addStatusSelect) addStatusSelect.innerHTML = buildStatusOptions('SB');
  const grid=document.getElementById('mStatusGrid');
  if(grid) grid.innerHTML = buildStatusButtons(selected);
  updateStatusPicker();
}

function buildStatusButtons(selected='SB'){
  const codes = getStatusCodes();
  return codes.map(id=>{
    const meta = STATUS_META[id] || { label: id };
    const label = id === 'SB' && (!meta.label || meta.label.toLowerCase() === 'standby') ? 'Stand By' : meta.label;
    return `<label class="st-pick${id===selected?' active':''}" data-status="${id}" style="--st-bg:${meta.bg||'#ECEFF1'};--st-fg:${meta.fg||'#37474F'}" onclick="setStatusFromGroup('${id}')">
      <input type="radio" name="mStatusGroup" value="${id}"${id===selected?' checked':''}>
      <span class="st-pick-code">${id}</span>
      <span class="st-pick-label">${label}</span>
    </label>`;
  }).join('');
}

function setStatusFromGroup(value){
  if(currentModalInRoom && value!=='R') return;
  const select=document.getElementById('mStatus');
  if(select) select.value=value;
  updateStatusPicker();
  onStatusChange();
}

function updateStatusPicker(){
  const grid=document.getElementById('mStatusGrid');
  const select=document.getElementById('mStatus');
  if(!grid||!select) return;
  const value=select.value||'SB';
  grid.querySelectorAll('.st-pick').forEach(el=>{
    const active=el.dataset.status===value;
    el.classList.toggle('active',active);
    const radio=el.querySelector('input');
    if(radio) radio.checked=active;
  });
}

function updateStatusChangeSummary(){
  const el=document.getElementById('statusChangeSummary');
  if(!el) return;
  if(!editKey){el.innerHTML='';return;}
  const c=Object.values(state[editKey.depot]||{}).find(x=>x.id===editKey.id);
  const select=document.getElementById('mStatus');
  if(!c||!select){el.innerHTML='';return;}
  const newStatus=select.value;
  const cur = editKey.day ? getFinalStatusForDay(c,editKey.day)||'' : c.status||'';
  if(cur===newStatus){
    el.innerHTML=`<div class="stc-row"><span class="stc-same">Same status</span><span class="stc-note">Refreshes the trailing segment — timestamps are kept.</span></div>`;
    return;
  }
  el.innerHTML=`<div class="stc-row">
    <span class="stc-from">${statusBadgeHtml(cur,'month-mini-badge')}</span>
    <span class="stc-arrow">→</span>
    <span class="stc-to">${statusBadgeHtml(newStatus,'month-mini-badge')}</span>
  </div>
  <div class="stc-note">The new status is recorded on top of the current one — resting time is never overwritten, timestamps are kept in the summary.</div>`;
}

function populateDesignationSelect(selected='locomotive_driver'){
  const addGradeSelect=document.getElementById('addGrade');
  if(addGradeSelect) addGradeSelect.innerHTML = getDesignationOptions(selected);
}

function populateLoginDepotOptions(selected='HQ'){
  const loginDepotSelect=document.getElementById('lDepot');
  if(!loginDepotSelect) return;
  const options=['HQ',...getActiveDepots()].map(depot=>{
    const label = depot==='HQ' ? 'HQ Headquarters - View all depots' : `${depot} Depot`;
    return `<option value="${depot}"${depot===selected?' selected':''}>${label}</option>`;
  }).join('');
  loginDepotSelect.innerHTML = options;
}


function normalizeCrewGradePayload(payload){
  if(payload && Object.prototype.hasOwnProperty.call(payload,'grade')){
    payload.grade = normalizeDesignation(payload.grade);
  }
  return payload;
}

async function migrateCrewDesignationKeys(){
  if(!db) return;
  const snap = await getDocs(collection(db,'crew'));
  if(snap.empty) return;
  const batch = writeBatch(db);
  let updated = 0;
  snap.forEach(docSnap=>{
    const data = docSnap.data();
    const normalizedGrade = normalizeDesignation(data.grade);
    if(!normalizedGrade || normalizedGrade === data.grade) return;
    batch.set(doc(db,'crew',docSnap.id),{grade:normalizedGrade},{merge:true});
    updated++;
  });
  if(updated>0){
    await batch.commit();
    setLog(`Normalized designation keys for ${updated} crew record(s).`);
  }
}

async function seedBackendUsers(){
  if(!db) return;
  await seedDepotMetaIfEmpty();
  await loadDepotMeta();
  await seedDesignationMetaIfEmpty();
  await loadDesignationMeta();
  await migrateCrewDesignationKeys();
  await seedUsersIfEmpty();
  await seedStatusMetaIfEmpty();
  await loadStatusMeta();
  await seedReportMetaIfEmpty();
  await loadReportMeta();
  await seedTrainTypeMetaIfEmpty();
  await loadTrainTypeMeta();
  await seedShiftMetaIfEmpty();
  await loadShiftMeta();
  await loadAccessMeta();
  populateDesignationSelect();
  populateStatusSelects();
  populateTrainTypeSelect();
  populateShiftSelects();
}

function normalizeReportMetaRecord(docSnapOrData){
  const data = docSnapOrData?.data ? docSnapOrData.data() : docSnapOrData;
  const id = data?.id || docSnapOrData?.id || docSnapOrData?.record_id;
  if(!id) return null;
  return {
    id,
    label: String(data.label || id),
    description: String(data.description || ''),
    reportType: String(data.reportType || 'status'),
    buttonText: String(data.buttonText || 'Run'),
    visible: data.visible !== false,
    order: Number.isFinite(data.order) ? data.order : 999,
    builder_layout: String(data.builder_layout || 'table'),
    builder_columns: Array.isArray(data.builder_columns) ? data.builder_columns : [],
    builder_filters: Array.isArray(data.builder_filters) ? data.builder_filters : [],
    builder_group_by: data.builder_group_by ?? null,
  };
}

async function seedReportMetaIfEmpty(){
  if(Object.keys(REPORT_TEMPLATES).length) return;
  REPORT_TEMPLATES = Object.fromEntries(DEFAULT_REPORT_TEMPLATES.map(meta => [meta.id, meta]));
  if(!db) return;
  try {
    const batch = writeBatch(db);
    DEFAULT_REPORT_TEMPLATES.forEach(meta => {
      batch.set(doc(db, 'reportMeta', meta.id), meta, { merge: true });
    });
    await batch.commit();
  } catch (err) {
    console.warn('Failed to seed report metadata', err);
  }
}

async function loadReportMeta(){
  if(!db){
    return await loadLocalReportMeta();
  }
  try{
    const snap = await getDocs(collection(db,'reportMeta'));
    const templates = {};
    snap.forEach(docSnap=>{
      const meta = normalizeReportMetaRecord(docSnap);
      if(meta) templates[meta.id] = meta;
    });
    if(Object.keys(templates).length === 0){
      REPORT_TEMPLATES = Object.fromEntries(DEFAULT_REPORT_TEMPLATES.map(meta => [meta.id, meta]));
    } else {
      REPORT_TEMPLATES = templates;
    }
  }catch(err){
    console.error('Failed to load report metadata',err);
    REPORT_TEMPLATES = Object.fromEntries(DEFAULT_REPORT_TEMPLATES.map(meta => [meta.id, meta]));
  }
}

async function loadLocalReportMeta(){
  try{
    const data = await fetchLocalAdminMeta();
    const templates = {};
    (data.reportMeta||[]).forEach(item=>{
      const meta = normalizeReportMetaRecord(item);
      if(meta) templates[meta.id] = meta;
    });
    if(Object.keys(templates).length === 0){
      REPORT_TEMPLATES = Object.fromEntries(DEFAULT_REPORT_TEMPLATES.map(meta => [meta.id, meta]));
    } else {
      REPORT_TEMPLATES = templates;
    }
  }catch(err){
    console.error('Failed to load local report metadata',err);
    REPORT_TEMPLATES = Object.fromEntries(DEFAULT_REPORT_TEMPLATES.map(meta => [meta.id, meta]));
  }
}

function getReportTemplates(){
  return Object.values(REPORT_TEMPLATES).filter(meta=>meta.visible).sort((a,b)=>a.order - b.order || String(a.label).localeCompare(String(b.label)));
}

async function ensureRestCountdownSample(){
  // Do not seed any static demo crew records. Rest countdown entries must come from MySQL.
  return;
}

function attachListeners(depots){
  listeners.forEach(u=>u());listeners=[];
  setSyncStatus('spin','Connecting…');
  /*depots.forEach(depot=>{
    if(!state[depot])state[depot]={};
    const unsub=onSnapshot(query(collection(db,'crew'), where('depot','==',depot)), snap=>{
      snap.docChanges().forEach(ch=>{const d=ch.doc.data();const id=d.id||ch.doc.id;if(ch.type==='removed')delete state[depot][id];else state[depot][id]=d;});
      setSyncStatus('ok','Live');
      syncCurrentPageFromLiveData();
    },err=>{setSyncStatus('err','Sync error');setLog('Error: '+err.message);});
    listeners.push(unsub);
  });*/
  // A single request avoids six simultaneous polling calls for HQ users.
  const source=hasGlobalAccess()
    ? collection(db,'crew')
    : query(collection(db,'crew'),where('depot','==',depots[0]||currentUser?.depot));
  const unsub=onSnapshot(source,snap=>{
    snap.docChanges().forEach(ch=>{
      const d=ch.doc.data();const id=d.id||ch.doc.id;const depot=normalizeCrewDepot(d.depot||currentUser?.depot);
      if(!depot)return;
      d.depot=depot;
      if(!state[depot])state[depot]={};
      if(ch.type==='removed')delete state[depot][id];else state[depot][id]=d;
    });
    setSyncStatus('ok','Live');
    syncCurrentPageFromLiveData();
  },err=>{setSyncStatus('err','Sync error');setLog('Error: '+err.message);});
  listeners.push(unsub);
}

function syncCurrentPageFromLiveData(){
  if (['rest','dashboard','roster','monthly','reports'].includes(currentPage)) {
    refreshPage();
  } else {
    updateCountdownsInTable();
  }
}

/* ════════ WRITE ═══════════════════════════════════════════════════════════ */
async function writeCrewDoc(depot,id,updates){
  normalizeCrewGradePayload(updates);
  if(!state[depot]) state[depot] = {};
  const previous = state[depot][id] ? { ...state[depot][id] } : null;
  state[depot][id] = { ...state[depot][id], ...updates };
  refreshPage();
  if(demoMode||!db){return;}
  try {
    const recordId=state[depot]?.[id]?.record_id||`${depot}_${id}`;
    await setDoc(doc(db,'crew',recordId),{...updates,lastUpdated:serverTimestamp()},{merge:true});
  } catch(err) {
    if(previous) state[depot][id] = previous;
    throw err;
  }
}
async function addCrewDoc(depot,obj){
  normalizeCrewGradePayload(obj);
  if(!state[depot]) state[depot] = {};
  state[depot][obj.id]=obj;
  refreshPage();
  if(demoMode||!db){return;}
  await setDoc(doc(db,'crew',`${depot}_${obj.id}`),{...obj,lastUpdated:serverTimestamp()});
}

/* ════════ AUTH ════════════════════════════════════════════════════════════ */
async function doLogin(){
  const user=document.getElementById('lUser')?.value.trim().toLowerCase();
  const pass=document.getElementById('lPass')?.value;
  const err=document.getElementById('loginErr');err.style.display='none';
  if(!user||!pass){err.textContent='Enter username and password.';err.style.display='block';return;}
  let acct=null;
  if(db){
    try{
      const resp = await fetch('/mysql/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify({ username: user, password: pass }),
      });
      if (!resp.ok) {
        err.textContent = resp.status === 401 ? 'Incorrect username or password.' : 'Unable to verify credentials.';
        err.style.display = 'block';
        return;
      }
      const data = await resp.json();
      const role = data.role || data.role_code || '';
      const depot = data.depot || data.depot_code || 'HQ';
      acct = {
        depot,
        name: data.name || user,
        isHQ: !!data.isHQ || !!data.is_hq || role === 'super_admin' || role === 'hq_admin' || depot === 'HQ',
        isSuperAdmin: !!data.isSuperAdmin || !!data.is_super_admin || role === 'super_admin',
        role,
      };
    } catch (authErr) {
      console.error('User lookup failed', authErr);
      err.textContent = 'Unable to verify credentials.';
      err.style.display = 'block';
      return;
    }
  }
  if(!acct){
    err.textContent='Account not found.';
    err.style.display='block';
    return;
  }
  setCurrentUser({ username:user,depot:acct.depot,name:acct.name,isHQ:acct.isHQ,isSuperAdmin:!!acct.isSuperAdmin,role:acct.role||'' });
  if(document.getElementById('app')){
    document.getElementById('app').classList.add('show');
  }
  const lds=currentUser.isHQ?getActiveDepots():[currentUser.depot];
  if(db){
    await seedDepotMetaIfEmpty();
    await loadDepotMeta();
    await seedDesignationMetaIfEmpty();
    await loadDesignationMeta();
    await seedStatusMetaIfEmpty();
    await loadStatusMeta();
    populateStatusSelects();
    populateDesignationSelect();
    await Promise.all(lds.map(seedDepotIfEmpty));
    await ensureRestCountdownSample();
    attachListeners(lds);
  }
  persistSession();
  goPage('monthly');
  setInterval(updateClock,1000);updateClock();
  if(cdInterval)clearInterval(cdInterval);
  cdInterval=setInterval(()=>{checkRestExpirations();if(currentPage==='rest')renderRest();else updateCountdownsInTable();},10000);
  setLog(`Signed in - ${currentUser.name}`);
}

async function doLogout(){
  try {
    await fetch('/logout', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json',
      },
    });
  } catch (err) {
    console.warn('Logout request failed', err);
  }
  listeners.forEach(u=>u());listeners=[];if(cdInterval)clearInterval(cdInterval);
  currentUser=null;hqDepotView='all';
  persistSession();
  updateSidebarSections();
  if(document.getElementById('app')){
    document.getElementById('app').classList.remove('show');
  }
  if(document.getElementById('lUser')) document.getElementById('lUser').value='';
  if(document.getElementById('lPass')) document.getElementById('lPass').value='';
  window.location.href = '/login';
}

/* ════════ HELPERS ═════════════════════════════════════════════════════════ */
function setLog(m){const el=document.getElementById('logText');if(el)el.textContent=fmtTime(new Date())+' - '+m;}
function updateClock(){const el=document.getElementById('tbClock');if(el)el.textContent=fmtTime(new Date());}
function refreshPage(force=false){
  // Skip refresh if a modal is open (user is editing)
  const modalOpen = document.getElementById('modal')?.classList.contains('open') || document.getElementById('addModal')?.classList.contains('open');
  if(modalOpen) return;
  
  // Debounce rapid refreshes
  const now = Date.now();
  if(!force && now - lastRefreshTime < MIN_REFRESH_INTERVAL) return;
  lastRefreshTime = now;
  
  const p={dashboard:renderDashboard,roster:renderRoster,rest:renderRest,monthly:renderMonthly,reports:renderReports,admin:renderAdmin};
  if(p[currentPage])p[currentPage]();
}
function setSyncStatus(t,m){
  const dot=document.getElementById('syncDot');const lbl=document.getElementById('syncLabel');if(!dot)return;
  dot.className='sd sd-'+t;lbl.textContent=m;
  const sb=document.getElementById('syncBadge');sb.classList.remove('hide');if(t==='ok')setTimeout(()=>sb.classList.add('hide'),3000);
  const ld=document.getElementById('tbLiveDot');const lt=document.getElementById('tbLiveTxt');
  if(ld)ld.style.background=t==='ok'?'#69F0AE':t==='err'?'#EF5350':'#FFB300';
  if(lt)lt.textContent=t==='ok'?'Live':t==='err'?'Offline':'Syncing…';
}
function setLoginHint(demo){
  const hintEl = document.getElementById('loginHint');
  if (!hintEl) return;
  hintEl.innerHTML = demo ? 'Demo mode - no sync.' : 'Use MySQL user credentials to sign in.';
}

/* ════════ NAVIGATION ══════════════════════════════════════════════════════ */
function goPage(p){
  if (p === 'admin') {
    window.location.href = '/admin';
    return;
  }
  console.log('goPage called', p);
  (function(){
    currentPage=p;if(p!=='roster')activeFilter='all';
    const path = PAGE_PATHS[p] || '/';
    try{
      if (window.location.pathname !== path) {
        history.pushState({ page: p }, '', path);
      } else {
        history.replaceState({ page: p }, '', path);
      }
    }catch(e){
      console.warn('history push failed',e);
    }
    document.querySelectorAll('.sb-item').forEach(e=>e.classList.remove('active'));
    const el=document.getElementById('sb-'+p);if(el)el.classList.add('active');
    const titles={dashboard:'Dashboard',roster:'Crew Roster',rest:'Rest Countdowns',monthly:'Monthly View',reports:'Reports'};
    document.getElementById('phTitle').textContent=titles[p]||p;
    document.title = `KR Crew System - ${titles[p]||p}`;
    // Render directly: this file is already part of the main Vite bundle. A
    // dynamic import here resolved relative to the hashed bundle and caused a
    // failed network request before every navigation.
    try{ refreshPage(true); }catch(err){ console.warn('refreshPage failed',err); }
    persistSession();
  })();
}

window.addEventListener('popstate', event => {
  const pageFromState = event.state?.page;
  const pageFromPath = getPageFromPathname(window.location.pathname);
  const page = pageFromState || pageFromPath || currentPage;
  if (page && page !== currentPage) {
    currentPage = page;
    document.querySelectorAll('.sb-item').forEach(e=>e.classList.remove('active'));
    const el=document.getElementById('sb-'+page);if(el)el.classList.add('active');
    refreshPage(true);
  }
});

/* ════════ DASHBOARD ═══════════════════════════════════════════════════════ */
function renderDashboard(){
  const depots=currentUser.isHQ?getActiveDepots():[currentUser.depot];
  const all=getAllCrew(state, depots);const c=cts(all);
  safeSetText('phSub', `Live booking board · ${MONTH_NAME}`);
  safeSetInner('phActions', '');

  // Build KPI list dynamically from status codes so new statuses propagate automatically.
  const kpis = [['TOT','Total crew',all.length]];
  getStatusCodes().forEach(code => {
    kpis.push([code, getStatusMeta(code).label || code, c[code]||0]);
  });
  let html=kpiHtml(kpis);

  const restingDrivers=all.filter(x=>x.status==='R'&&isDesignationRestEligible(x.grade)&&restSecondsLeft(x)!==null&&restSecondsLeft(x)<3600);
  if(restingDrivers.length>0){
    html+=`<div class="alert-banner">⏰ ${restingDrivers.length} driver(s) completing rest within the hour: ${restingDrivers.map(x=>x.name.split(' ')[0]).join(', ')}</div>`;
  }
  const sick=all.filter(x=>x.status==='SK');
  if(sick.length>0)html+=`<div class="alert-banner">⚠ Sick crew: ${sick.map(x=>x.name.split(' ')[0]).join(', ')}</div>`;

  if(currentUser.isHQ){
    html+=`<div class="sec-hdr"><span class="sec-title">Depot overview</span></div><div class="depot-grid">`;
    getActiveDepots().forEach(depot=>{
      const crew=Object.values(state[depot]||[]);const dc=cts(crew);const tot=crew.length||1;
      const col=DEPOT_COLORS[depot];const hasIssue=(dc.SK||0)>0||(dc.ABS||0)>0||(dc.NTB||0)>0;
      const lastUpd=crew.length?fmtLastUpd(crew.reduce((a,b)=>(a.lastUpdated||'')>(b.lastUpdated||'')?a:b).lastUpdated):'-';
      html+=`<div class="depot-card" onclick="setHqDepotView('${depot}');goPage('roster')">
        <div class="dc-hdr"><span class="dc-name" style="color:${col}">${depot}</span><div class="dc-alert-dot" style="background:${hasIssue?'#E53935':'#43A047'}"></div></div>
        <div class="dc-bars">`;
      // Render status bars dynamically using configured statuses and their colors.
      getStatusCodes().forEach(code=>{
        const meta = getStatusMeta(code);
        const lbl = meta.label || code;
        const col2 = meta.bg || '#ECEFF1';
        const n = dc[code]||0; const pct = Math.round((n/tot)*100);
        html+=`<div class="dc-bar-row"><span class="dc-bar-lbl">${lbl}</span><div class="dc-bar-track"><div class="dc-bar-fill" style="width:${pct}%;background:${col2}"></div></div><span class="dc-bar-val">${n}</span></div>`;
      });
      html+=`</div><div class="dc-foot"><span>${crew.length} crew</span><span class="dc-online"><div class="dc-online-dot"></div>Updated ${lastUpd}</span></div></div>`;
    });
    html+=`</div>`;
  } else {
    html+=`<div class="sec-hdr"><span class="sec-title">Today - ${currentUser.depot}</span><button class="btn btn-green btn-sm no-print" onclick="openAddModal()">+ Add crew</button></div>`;
    html+=crewTableHtml(currentUser.depot,false,true);
  }
  safeSetInner('pbody', html);
}

/* ════════ ROSTER ══════════════════════════════════════════════════════════ */
function renderRoster(){
  const depots=currentUser.isHQ?(hqDepotView==='all'?getActiveDepots():[hqDepotView]):[currentUser.depot];
  const label=currentUser.isHQ?(hqDepotView==='all'?'All Depots':hqDepotView+' Depot'):currentUser.depot+' Depot';
  safeSetText('phSub', `${label} · ${MONTH_NAME}`);
  const allCrew=getAllCrew(state, depots);const c=cts(allCrew);
  safeSetInner('phActions', currentUser.isHQ?''
    :`<button class="btn btn-ghost btn-sm no-print" onclick="openAddModal()">+ Add crew</button>`);

  let html='';
  if(currentUser.isHQ){
    html+=`<div style="margin-bottom:11px;display:flex;gap:6px;align-items:center;flex-wrap:wrap">
      <button class="pill pa" onclick="setHqDepotView('all')">All</button>
      ${getActiveDepots().map(d=>`<button class="pill" onclick="setHqDepotView('${d}')" style="color:${DEPOT_COLORS[d]}">${d}</button>`).join('')}
    </div>`;
  }
  const rosterKpis = [['TOT','Total',allCrew.length]];
  getStatusCodes().forEach(code=> rosterKpis.push([code,getStatusMeta(code).label||code,c[code]||0]));
  html+=kpiHtml(rosterKpis,'repeat(auto-fit,minmax(80px,1fr))');
  const showDepot=currentUser.isHQ&&hqDepotView==='all';
  html+=crewTableHtml(hqDepotView==='all'&&currentUser.isHQ?'all':depots[0],showDepot,true);
  safeSetInner('pbody', html);
  updatePills();
}

function crewTableHtml(depotOrAll,showDepotCol,editable){
  const depots=depotOrAll==='all'?getActiveDepots():[depotOrAll];
  let allCrew=getAllCrew(state, depots).sort((a,b)=>a.name.localeCompare(b.name));
  if(activeFilter!=='all')allCrew=allCrew.filter(c=>c.status===activeFilter);

  let html=`<div class="tbl-card">
    <div class="tbl-toolbar no-print">
      <div class="tt-search"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg><input type="text" id="crewSearch" placeholder="Search name or ID…" oninput="filterSearch()"></div>
      <div class="pills">
        <span class="pill pa" id="pill-all" onclick="setFilter('all')">All</span>
        ${STATUSES.map(s=>`<span class="pill" id="pill-${s}" onclick="setFilter('${s}')">${STATUS_META[s].label}</span>`).join('')}
      </div>
    </div>
    <div class="tbl-wrap"><table>
      <thead><tr>
        <th>Staff No.</th><th>Name / Designation</th>
        ${showDepotCol?'<th>Depot</th>':''}
        <th>Rest location</th>
        <th>Route</th><th>Shift</th><th>Status</th><th>Train Type</th><th>Depart</th>
        <th>Rest Countdown</th><th>Since</th><th>Notes</th>
      </tr></thead>
      <tbody id="crewTbody">`;

  if(allCrew.length===0){html+=`<tr><td colspan="${12 + (showDepotCol ? 1 : 0)}" class="no-rows">No crew match this filter.</td></tr>`;}
  else allCrew.forEach((c,i)=>{
    const [abg,afc]=AVT_PAL[i%AVT_PAL.length];
    const m=STATUS_META[c.status]||{label:c.status};
    const dc=DEPOT_COLORS[c.depot]||'#37474F';
    const sec=restSecondsLeft(c);
    const maxH=getRestHours(c);
    let cdHtml='<span style="color:var(--text3);font-size:11px">-</span>';
    if(c.status==='R'&&isDesignationRestEligible(c.grade)){
      if(sec===null){cdHtml=`<span style="font-size:10px;color:var(--text2)">No start time</span>`;}
      else if(sec<=0){cdHtml=`<span class="cd-done">→ Standby</span>`;}
      else{const cl=cdClass(sec,maxH);const pct=Math.round((sec/(maxH*3600))*100);cdHtml=`<div class="countdown-cell"><span class="cd-text ${cl}" id="cd-${c.depot}-${c.id}">${fmtCountdown(sec)}</span><div class="cd-bar-wrap"><div class="cd-bar cd-${cl}" style="width:${pct}%" id="cdb-${c.depot}-${c.id}"></div></div></div>`;}
    }
    html+=`<tr>
      <td style="font-size:11px;color:var(--text3);font-family:var(--mono)">${getCrewStaffNumberLabel(c)}</td>
      <td><div class="nm nm-click" onclick="openCrewDetails('${c.depot}','${c.id}')" title="View details & status history"><div class="avt" style="background:${abg};color:${afc}">${initials(c.name)}</div><div><strong>${c.name}</strong><span>${getDesignationLabel(c.grade)}</span></div></div></td>
      ${showDepotCol?`<td><span style="color:${dc};font-weight:700">${c.depot}</span></td>`:''}
      <td style="font-size:11px;color:${c.awayDepot?'var(--kr-red)':'var(--text3)'}">${c.awayDepot?`${c.awayDepot} (away)`:'Home'}${c.rrRoomName?` · <span style="font-size:10px;color:${c.rrCheckedOut?'var(--standby)':'var(--kr-red)'}">${c.rrRoomName}${c.rrRoomDepot&&c.rrRoomDepot!==c.depot?` (${c.rrRoomDepot})`:''} ${c.rrCheckedOut?'· out':'· in'}</span>`:''}</td>
      <td style="font-size:11px">${c.route||'-'}</td>
      <td style="color:var(--text3);font-size:10px">${getCrewShiftLabel(c)}</td>
      <td><span class="badge bd-${c.status}">${m.label}</span></td>
      <td>${c.status==='BK'&&c.trainType?`<span style="${getTrainTypeBadgeStyle(c.trainType)}">${getTrainTypeLabel(c.trainType)}</span>`:'-'}</td>
      <td style="font-size:11px;font-family:var(--mono);color:${c.status==='BK'&&c.bookTime?'var(--booked)':'var(--text3)'};font-weight:${c.status==='BK'&&c.bookTime?'700':'400'}">${c.status==='BK'&&c.bookTime?c.bookTime:'-'}</td>
      <td>${cdHtml}</td>
      <td style="color:var(--text3);font-size:11px">${c.since||'-'}</td>
      <td style="font-size:11px;color:var(--text2);max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${c.notes||''}">${c.notes||'-'}</td>
    </tr>`;
  });
  html+=`</tbody></table></div></div>`;
  return html;
}

function updateCountdownsInTable(){
  const depots=currentUser.isHQ?getActiveDepots():[currentUser.depot];
  const all=getAllCrew(state, depots);
  all.forEach(c=>{
    if(c.status!=='R'||!isDesignationRestEligible(c.grade))return;
    const sec=restSecondsLeft(c);if(sec===null)return;
    const textEl=document.getElementById(`cd-${c.depot}-${c.id}`);
    const barEl=document.getElementById(`cdb-${c.depot}-${c.id}`);
    if(!textEl)return;
    if(sec<=0){textEl.parentElement.parentElement.innerHTML=`<span class="cd-done">→ Standby</span>`;return;}
    const cl=cdClass(sec,getRestHours(c));
    const pct=Math.round((sec/(getRestHours(c)*3600))*100);
    textEl.textContent=fmtCountdown(sec);
    textEl.className=`cd-text ${cl}`;
    if(barEl){barEl.className=`cd-bar cd-${cl}`;barEl.style.width=pct+'%';}
  });
}

/* ════════ REST PAGE ═══════════════════════════════════════════════════════ */
function renderRest(){
  safeSetText('phSub', 'Live rest countdowns');
  safeSetInner('phActions', '');
  const depots=currentUser.isHQ?getActiveDepots():[currentUser.depot];
  const all=getAllCrew(state, depots).filter(c=>isDesignationRestEligible(c.grade));
  const resting=all.filter(c=>c.status==='R');
  const standby=all.filter(c=>c.status==='SB');
  const booked=all.filter(c=>c.status==='BK');

  let html=kpiHtml([['TOT','Total Drivers',all.length],['R','Resting',resting.length],['SB','Standby',standby.length],['BK','Booked',booked.length]],'repeat(4,1fr)');
  html+=`<div class="sec-hdr"><span class="sec-title">Resting crew - live countdown</span><span style="font-size:11px;color:var(--text2)">Auto-updates every 10s · Crew auto-promoted to Standby on expiry</span></div>`;

  if(resting.length===0){html+=`<div style="background:#fff;border:1px solid var(--border);border-radius:var(--rl);padding:30px;text-align:center;color:var(--text3)">No crew currently resting.</div>`;}
  else{
    html+=`<div class="rest-grid" id="restGrid">`;
    resting.sort((a,b)=>{const sa=restSecondsLeft(a)??999999;const sb=restSecondsLeft(b)??999999;return sa-sb;}).forEach((c,i)=>{
      const [abg,afc]=AVT_PAL[i%AVT_PAL.length];
      const sec=restSecondsLeft(c);const maxH=getRestHours(c);
      const restLocation = c.awayDepot && c.awayDepot !== c.depot ? `Away: ${c.awayDepot} (Home: ${c.depot})` : `Home: ${c.depot}`;
      if(sec===null){html+=`<div class="rest-card ok"><div class="avt rest-avt" style="background:${abg};color:${afc}">${initials(c.name)}</div><div class="rest-info"><div class="rest-name">${c.name}</div><div class="rest-depot">${restLocation} · ${getDesignationLabel(c.grade)}</div></div><div class="rest-cd"><span style="font-size:11px;color:var(--text2)">No start time set</span></div></div>`;return;}
      const cl=sec<=0?'done':cdClass(sec,maxH);
      const pct=sec<=0?100:Math.round(((maxH*3600-sec)/(maxH*3600))*100);
      const timeStr=sec<=0?'COMPLETE':fmtCountdown(sec);
      const colMap={ok:'var(--countdown-ok)',warn:'var(--countdown-warn)',crit:'var(--countdown-crit)',done:'var(--standby)'};
      html+=`<div class="rest-card ${cl}">
        <div class="avt rest-avt" style="background:${abg};color:${afc}">${initials(c.name)}</div>
        <div class="rest-info">
          <div class="rest-name">${c.name}</div>
          <div class="rest-depot">${restLocation} · ${getDesignationLabel(c.grade)} · ${maxH}h rest</div>
          <div style="font-size:10px;color:var(--text2);margin-top:2px">Started: ${c.restStarted?fmtTime(new Date(c.restStarted)):'-'}${c.rrRoomName?` · Room: ${c.rrRoomName}${c.rrRoomDepot&&c.rrRoomDepot!==c.depot?` (${c.rrRoomDepot})`:''} (${c.rrCheckedOut?'checked out':'in room'})`:''}</div>
        </div>
        <div class="rest-cd">
          <div class="rest-cd-time" style="color:${colMap[cl]}" id="rcd-${c.depot}-${c.id}">${timeStr}</div>
          <div class="rest-cd-label">${sec<=0?'→ Standby':'remaining'}</div>
          <div class="rest-cd-bar"><div class="rest-cd-fill" style="width:${pct}%;background:${colMap[cl]}" id="rcdbar-${c.depot}-${c.id}"></div></div>
        </div>
      </div>`;
    });
    html+=`</div>`;
  }
  html+=`<div class="divider"></div><div class="sec-hdr"><span class="sec-title">Rest rules</span></div>`;
  html+=`<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:9px">`;
  const homeHours=getRestHours('Changamwe');
  const awayHours=getRestHours({depot:'Changamwe',awayDepot:'Mtito'});
  html+=`<div style="background:#fff;border:1px solid var(--border);border-radius:var(--r);padding:12px;display:flex;align-items:center;gap:10px"><div style="width:12px;height:12px;border-radius:50%;background:${DEPOT_COLORS.Changamwe};flex-shrink:0"></div><div><div style="font-size:12px;font-weight:700">Home depot</div><div style="font-size:11px;color:var(--text2)">${homeHours}h rest period</div></div></div>`;
  html+=`<div style="background:#fff;border:1px solid var(--border);border-radius:var(--r);padding:12px;display:flex;align-items:center;gap:10px"><div style="width:12px;height:12px;border-radius:50%;background:${DEPOT_COLORS.Mtito};flex-shrink:0"></div><div><div style="font-size:12px;font-weight:700">Away depot</div><div style="font-size:11px;color:var(--text2)">${awayHours}h rest period</div></div></div>`;
  html+=`</div>`;
  safeSetInner('pbody', html);
  // Live tick for rest page
  if(cdInterval)clearInterval(cdInterval);
  cdInterval=setInterval(()=>{
    checkRestExpirations();
    resting.forEach(c=>{
      const sec=restSecondsLeft(c);if(sec===null)return;
      const tel=document.getElementById(`rcd-${c.depot}-${c.id}`);
      const bel=document.getElementById(`rcdbar-${c.depot}-${c.id}`);
      if(!tel)return;
      const maxH=getRestHours(c);
      if(sec<=0){tel.textContent='COMPLETE';tel.style.color='var(--standby)';if(bel){bel.style.width='100%';bel.style.background='var(--standby)';}return;}
      const cl=cdClass(sec,maxH);const pct=Math.round(((maxH*3600-sec)/(maxH*3600))*100);
      const colMap={ok:'var(--countdown-ok)',warn:'var(--countdown-warn)',crit:'var(--countdown-crit)'};
      tel.textContent=fmtCountdown(sec);tel.style.color=colMap[cl]||'var(--countdown-ok)';
      if(bel){bel.style.width=pct+'%';bel.style.background=colMap[cl]||'var(--countdown-ok)';}
    });
  },1000);
}

/* ════════ MONTHLY ═════════════════════════════════════════════════════════ */
function renderMonthly(){
  const depots=currentUser.isHQ?(hqDepotView==='all'?getActiveDepots():[hqDepotView]):[currentUser.depot];
  const allCrew=getAllCrew(state, depots).sort((a,b)=>a.name.localeCompare(b.name));
  const showDepot=currentUser.isHQ&&hqDepotView==='all';
  safeSetText('phSub', `${MONTH_NAME} - Daily Position Register`);
  safeSetInner('phActions', `
    ${currentUser.isHQ?`<select class="sel-sm no-print" onchange="setHqDepotView(this.value);renderMonthly()"><option value="all">All depots</option>${getActiveDepots().map(d=>`<option value="${d}"${hqDepotView===d?' selected':''}>${d}</option>`).join('')}</select>`:''}
    <button class="btn btn-ghost btn-sm no-print" onclick="window.print()">Print</button>
    <button class="btn btn-primary btn-sm no-print" onclick="exportMonthlyCSV()">CSV</button>`);

  const maxDay = CD;
  const selectedDay = Math.max(1, Math.min(maxDay, selectedMonthDay || CD));
  const dayTotals = buildDayTotals(allCrew, selectedDay);
  const selectedDate = new Date(CY,CM,selectedDay);
  const selectedLabel = `${DAY_NAMES[selectedDate.getDay()]} ${selectedDay} ${MONTH_NAME.split(' ')[0]}`;
  if(!selectedMonthCrewKey && allCrew.length) selectedMonthCrewKey = `${allCrew[0].depot}::${allCrew[0].id}`;
  const selectedCrew = allCrew.find(c=>`${c.depot}::${c.id}`===selectedMonthCrewKey) || allCrew[0] || null;
  const selectedSegments = selectedCrew ? getDaySegments(selectedCrew, selectedDay) : [];
  const selectedFinal = selectedSegments.length ? selectedSegments[selectedSegments.length-1].status_code : '';
  const canEditToday = selectedCrew && selectedDay === CD && canManageCrew(selectedCrew.depot);
  const dayMinutes = selectedSegments.reduce((acc,seg)=>{acc[seg.status_code]=(acc[seg.status_code]||0)+segmentDurationMinutes(seg);return acc;},{});
  const days = Array.from({length:maxDay},(_,i)=>i+1);
  const monthSummaryForCrew = selectedCrew ? Object.entries(STATUS_META).map(([code])=>[
    code,
    days.filter(day=>getFinalStatusForDay(selectedCrew, day)===code).length
  ]).filter(([,count])=>count>0) : [];

  let html=`<div class="monthly-view">
    <div class="legend month-legend">
      ${Object.entries(STATUS_META).map(([code,m])=>`<div class="leg-item"><div class="leg-dot" style="background:${m.bg};border:1px solid ${m.fg}50"></div><b>${code}</b> = ${m.label}</div>`).join('')}
      <span class="month-stack-hint">Stacked cell = multiple statuses that day, click to view</span>
    </div>
    <div class="month-panel">
      <div class="month-main">
        <div class="month-register-card">
          <div class="month-register-head">
            <div><div class="month-register-title">Monthly view</div><div class="month-register-sub">${MONTH_NAME} - Daily position register</div></div>
            <div class="month-register-meta">${allCrew.length} crew - ${selectedLabel}</div>
          </div>
          <div class="month-wrap"><table class="month-tbl month-tbl-modern"><thead><tr>
            <th class="mc-name">Name</th>${showDepot?'<th class="mc-dep">Depot</th>':''}`;

  days.forEach(d=>{
    const dt=new Date(CY,CM,d);
    const we=dt.getDay()===0||dt.getDay()===6;
    const isTod=d===CD;
    const isSelected=d===selectedDay;
    html+=`<th class="${isSelected?'selected-header':''}" style="${we?'color:#E53935':''}${isTod?';background:#FFF8E1;color:var(--kr-red)':''}"><div>${d}</div><div style="font-size:8px">${DAY_NAMES[dt.getDay()]}</div></th>`;
  });
  html+=`<th style="background:#E8F5E9;color:#1B5E20">BK</th><th style="background:#E3F2FD;color:#0D47A1">SB</th><th style="background:#F3F2F5;color:#4A148C">R</th><th style="background:#FFF3E0;color:#E65100">L</th><th style="background:#FFEBEE;color:#B71C1C">SK</th><th style="background:#FEF2F2;color:#991B1B">ABS</th><th style="background:#ECEFF1;color:#37474F">NTB</th><th style="background:#FCE4EC;color:#AD1457">TO</th></tr></thead><tbody>`;

  allCrew.forEach(c=>{
    const activeRow = selectedCrew && selectedCrew.id===c.id && selectedCrew.depot===c.depot;
    html+=`<tr class="${activeRow?'month-row-selected':''}"><td class="mc-name" onclick="openCrewDetails('${c.depot}','${c.id}')" style="cursor:pointer" title="View details & status history"><strong>${c.name}</strong><div style="font-size:11px;color:var(--text2)">${getDesignationLabel(c.grade)}</div></td>`;
    if(showDepot)html+=`<td class="mc-dep" style="color:${DEPOT_COLORS[c.depot]};font-weight:700">${c.depot}</td>`;
    const sm={BK:0,SB:0,R:0,L:0,SK:0,ABS:0,T:0,NTB:0,TO:0};
    days.forEach(d=>{
      const daySegments = getDaySegments(c, d);
      const code=getFinalStatusForDay(c,d)||'';
      const dt=new Date(CY,CM,d);
      const we=dt.getDay()===0||dt.getDay()===6;
      const isEngineeringBooking = code==='BK' && String(c.trainType||'')==='Engineering';
      const isTod=d===CD;
      const isSelected=d===selectedDay;
      const cls=code?(code==='BK' ? (isEngineeringBooking ? 'day-BK day-BK-eng' : 'day-BK') : `day-${code}`):(we?'day-we':'');
      if(sm[code]!==undefined)sm[code]++;
      const selectedClass = isSelected && activeRow ? ' selected-col' : '';
      html+=`<td class="${cls} day-ed${isTod?' today-col':''}${selectedClass}">${renderMonthDayCell(daySegments, code)}</td>`;
    });
    html+=`<td class="mc-sBK">${sm.BK}</td><td class="mc-sSB">${sm.SB}</td><td class="mc-sR">${sm.R}</td><td class="mc-sL">${sm.L}</td><td class="mc-sSK">${sm.SK}</td><td class="mc-sABS">${sm.ABS}</td><td class="mc-sNTB">${sm.NTB}</td><td class="mc-sTO">${sm.TO}</td></tr>`;
  });

  html+=`<tr style="background:#0F172A"><td class="mc-name" style="color:#E0E0E0;font-weight:700;font-size:10px">BOOKED / DAY</td>${showDepot?'<td style="background:#0F172A"></td>':''}`;
  days.forEach(d=>{
    const bk=allCrew.filter(c=>getFinalStatusForDay(c,d)==='BK').length;
    const engineering=allCrew.filter(c=>getFinalStatusForDay(c,d)==='BK' && String(c.trainType||'')==='Engineering').length;
    html+=`<td class="${engineering>0?'mc-sBK-eng':'mc-sBK'}">${bk}</td>`;
  });
  html+=`<td colspan="7" style="color:rgba(255,255,255,.4);font-size:10px;text-align:left;padding-left:8px">Booked per day</td></tr>`;

  html+=`</tbody></table></div></div>
        <div class="month-info-note">A crew member can carry several statuses in one day. The stacked cell segments are sized by time held; daily totals count the final status.</div>
      </div>
      <aside class="month-side">
        <div class="month-side-card"><h3>Day timeline</h3><div class="summary-note">${selectedCrew?`${selectedCrew.name} - ${selectedDay} ${MONTH_NAME.split(' ')[0]}`:'No crew selected'}</div>${canEditToday?`<div style="margin-bottom:10px"><button class="btn btn-primary btn-sm" onclick="openDayEdit('${selectedCrew.depot}','${selectedCrew.id}',${selectedDay})">Edit today</button></div>`:''}<div class="month-timeline">${selectedSegments.length?selectedSegments.map((seg,index)=>{
          const meta=STATUS_META[seg.status_code]||{label:seg.status_code,bg:'#ECEFF1',fg:'#37474F'};
          return `<div class="month-timeline-row"><div class="month-timeline-rail"><span style="background:${meta.fg}"></span>${index<selectedSegments.length-1?'<i></i>':''}</div><div><div class="month-time">${seg.start_time} - ${seg.end_time}</div><div class="month-timeline-title">${statusBadgeHtml(seg.status_code,'month-mini-badge')} ${meta.label}</div><div class="month-timeline-note">${seg.note||meta.label}</div></div></div>`;
        }).join(''):`<div class="selected-day-none">No status recorded on this date.</div>`}</div></div>
        <div class="month-side-card"><h3>Day summary</h3><div class="status-side-list">${Object.entries(dayMinutes).length?Object.entries(dayMinutes).map(([code,mins])=>`<div class="status-side-item"><span>${STATUS_META[code]?.label||code}</span><strong>${Math.floor(mins/60)}h ${mins%60}m (${Math.round((mins/1440)*100)}%)</strong></div>`).join(''):`<div class="selected-day-none">No time split available.</div>`}</div><div class="summary-row"><div><strong>Final status</strong><div class="summary-note">Used for monthly totals</div></div>${selectedFinal?statusBadgeHtml(selectedFinal,'month-final-badge'):'-'}</div></div>
        <div class="month-side-card"><h3>Monthly summary</h3><div class="status-side-list">${monthSummaryForCrew.length?monthSummaryForCrew.map(([code,total])=>`<div class="status-side-item"><span class="status-side-chip status-${code}">${code}</span><span>${total}</span></div>`).join(''):`<div class="selected-day-none">No monthly records yet.</div>`}</div></div>
      </aside>
    </div>
  </div>`;
  safeSetInner('pbody', html);
}
/* ════════ REPORTS ═════════════════════════════════════════════════════════ */
function renderReports(){
  safeSetText('phSub', 'Export and download crew data');
  safeSetInner('phActions', '');
  const monthOptions=getRecentMonthOptions(12);
  const depots=currentUser.isHQ?getActiveDepots():[currentUser.depot];
  const all=getAllCrew(state, depots);const c=cts(all);
  const templates=getReportTemplates();
  let cards='';
  if(templates.length){
    cards = templates.map(meta=>{
      let paramRow='';
      if(meta.reportType==='monthly'){
        paramRow=`<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:10px"><select id="reportMonth" class="sel-sm no-print">${monthOptions}</select><button class="btn btn-primary btn-sm" onclick="runReport('${meta.id}')">${meta.buttonText}</button></div>`;
      } else if(meta.reportType==='utilization'){
        paramRow=`<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:10px"><select id="utilWindow" class="sel-sm no-print" onchange="updateReportSummary()"><option value="month">This month</option><option value="quarter">Last quarter</option><option value="90d">Last 90 days</option><option value="180d">Last 180 days</option><option value="365d">Last 365 days</option></select><button class="btn btn-primary btn-sm" onclick="runReport('${meta.id}')">${meta.buttonText}</button></div><div id="utilSummary" class="rep-note" style="margin-top:12px;color:var(--text2)">Choose a time window to preview utilization metrics.</div>`;
      } else {
        paramRow=`<button class="btn ${meta.reportType==='print'?'btn-ghost':'btn-primary'} btn-sm" onclick="runReport('${meta.id}')">${meta.buttonText}</button>`;
      }
      return `<div class="rep-card"><h3>${meta.reportType==='print'?'🖨 ':meta.reportType==='status'?'📊 ':meta.reportType==='monthly'?'📅 ':meta.reportType==='utilization'?'📈 ':''}${meta.label}</h3><p>${meta.description}</p>${paramRow}</div>`;
    }).join('');
  } else {
    cards = `<div class="rep-empty">
      <div style="padding:18px;border:1px dashed var(--border);border-radius:var(--r);background:#FFF8E1;color:#7A4F01">
        No report templates are configured yet. Use the Admin page to add or enable report templates, or use the direct export actions below.
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:16px">
        <button class="btn btn-primary btn-sm" onclick="exportCSV()">Export current status</button>
        <button class="btn btn-primary btn-sm" onclick="exportMonthlyCSV()">Download current month</button>
        <button class="btn btn-primary btn-sm" onclick="exportUtilizationCSV()">Export utilization</button>
        <button class="btn btn-primary btn-sm" onclick="exportAbsenceCSV()">Export absence</button>
      </div>
    </div>`;
  }
  // Build dynamic KPI block for reports page
  const reportKpis = [['TOT','Total crew',all.length]];
  getStatusCodes().forEach(code=> reportKpis.push([code,getStatusMeta(code).label||code,c[code]||0]));
  safeSetInner('pbody', `
  <div class="rep-grid">
    ${cards}
  </div>
  <hr class="divider">
  <div class="sec-hdr"><span class="sec-title">${MONTH_NAME} - snapshot</span></div>
  ${kpiHtml(reportKpis)}`);
  updateReportSummary();
}

function runReport(reportId){
  const report = REPORT_TEMPLATES[reportId];
  if(!report){
    alert('Report template not found.');
    return;
  }
  switch(report.reportType){
    case 'status':
      exportCSV();
      break;
    case 'monthly':
      exportMonthlyCSV(document.getElementById('reportMonth')?.value || MONTH_KEY);
      break;
    case 'utilization':
      exportUtilizationCSV(document.getElementById('utilWindow')?.value || 'month');
      break;
    case 'absence':
      exportAbsenceCSV();
      break;
    case 'print':
      goPage('monthly');
      setTimeout(()=>window.print(),500);
      break;
    case 'builder':
      runBuilderReport(report);
      break;
    default:
      alert(`Unknown report type: ${report.reportType}`);
  }
}

async function runBuilderReport(report){
  const target = document.getElementById('pbody');
  if(!target) return;
  safeSetInner('pbody', '<div class="rep-empty">Loading builder report…</div>');
  try{
    const resp = await fetch(`/reports/builder/${encodeURIComponent(report.id)}`, {cache:'no-store'});
    if(!resp.ok){
      let msg = `Builder report failed (${resp.status})`;
      try{ const j = await resp.json(); if(j.error) msg = j.error; }catch(e){}
      throw new Error(msg);
    }
    const data = await resp.json();
    renderBuilderTable(report, data);
  }catch(err){
    console.error('Builder report failed', err);
    safeSetInner('pbody', `<div class="rep-empty">${String(err?.message||'Failed to load report').replace(/[<>&"']/g, ch => ({'<':'&lt;','>':'&gt;','&':'&amp;','"':'&quot;',"'":'&#39;'}[ch]))}</div>`);
  }
}

function renderBuilderTable(report, data){
  const cols = Array.isArray(data?.columns) ? data.columns : [];
  const rows = Array.isArray(data?.rows) ? data.rows : [];
  if(!cols.length){
    safeSetInner('pbody', '<div class="rep-empty">This report has no columns configured.</div>');
    return;
  }
  const esc = v => String(v ?? '').replace(/[<>&"']/g, ch => ({'<':'&lt;','>':'&gt;','&':'&amp;','"':'&quot;',"'":'&#39;'}[ch]));
  const thead = `<tr>${cols.map(c=>`<th>${esc(c.label||c.key)}</th>`).join('')}</tr>`;
  const tbody = rows.length
    ? rows.map(r=>`<tr>${cols.map(c=>`<td>${esc(r[c.key])}</td>`).join('')}</tr>`).join('')
    : `<tr><td colspan="${cols.length}" style="text-align:center;color:var(--text3)">No rows returned.</td></tr>`;
  safeSetInner('pbody', `
    <div class="rep-card" style="max-width:100%">
      <h3>${esc(data.label || report.label || report.id)}</h3>
      <p>${rows.length} row${rows.length===1?'':'s'} · generated ${new Date().toLocaleTimeString('en-KE',{hour:'2-digit',minute:'2-digit'})}</p>
      <div class="tbl-wrap" style="margin-top:10px"><table><thead>${thead}</thead><tbody>${tbody}</tbody></table></div>
      <div style="margin-top:12px"><button class="btn btn-ghost btn-sm" onclick="goPage('reports')">Back to reports</button></div>
    </div>`);
}

function parseMonthKey(monthKey){
  const [y,m]=String(monthKey||'').split('-').map(Number);
  if(!y||!m||m<1||m>12) return null;
  return {year:y,month:m};
}
function formatMonthLabel(monthKey){
  const parsed=parseMonthKey(monthKey);
  if(!parsed) return monthKey||'';
  return new Date(parsed.year,parsed.month-1,1).toLocaleString('en-KE',{month:'long',year:'numeric'});
}
function getDaysInMonthKey(monthKey){
  const parsed=parseMonthKey(monthKey);
  return parsed? new Date(parsed.year,parsed.month,0).getDate() : 0;
}
function getRecentMonthKeys(count=12){
  const keys=[];
  for(let i=0;i<count;i++){
    const dt=new Date(CY,CM-i,1);
    keys.push(`${dt.getFullYear()}-${String(dt.getMonth()+1).padStart(2,'0')}`);
  }
  return keys;
}
function getRecentMonthOptions(count=12){
  return getRecentMonthKeys(count).map(key=>`<option value="${key}"${key===MONTH_KEY?' selected':''}>${formatMonthLabel(key)}</option>`).join('');
}
function buildMonthlyFromStatusSegments(item){
  if(!item||!Array.isArray(item.status_segments)) return item?.monthly||{};
  const monthly = {...(item.monthly||{})};
  item.status_segments.forEach(seg=>{
    if(!seg||typeof seg.day!=='number') return;
    monthly[`d${seg.day}`] = seg.status_code || '';
  });
  return monthly;
}
function getDaySegmentCode(item, day){
  return getFinalStatusForDay(item, day);
}
function buildStatusSegmentsForDay(item, day, status, opts = {}){
  const segments = Array.isArray(item?.status_segments) ? [...item.status_segments] : [];
  if(status===''){
    return segments.filter(x=>Number(x.day)!==Number(day));
  }
  const daySegs = segments.filter(x=>Number(x.day)===Number(day));
  const otherSegs = segments.filter(x=>Number(x.day)!==Number(day));
  const now = opts.now || new Date();
  const finalStatus = daySegs.length ? (daySegs[daySegs.length-1].status_code||daySegs[daySegs.length-1].status||'') : '';
  const restStart = status==='R' && item?.restStarted ? new Date(item.restStarted) : null;
  const restUsable = restStart && !isNaN(restStart.getTime());
  const startMin = status==='R' && restUsable ? timeToMinutes(fmtTime(restStart)) : timeToMinutes(fmtTime(now));
  const endMin = status==='R' && restUsable ? Math.min(1439, startMin + getRestHours(item)*60) : 1439;
  const note = opts.note || STATUS_META[status]?.label || status;
  // Same status again → refresh the trailing segment instead of stacking a duplicate.
  if(finalStatus===status){
    const last = daySegs[daySegs.length-1];
    last.status_code=status; last.status=status;
    last.end_time='23:59';
    last.note=note;
    return [...otherSegs,...daySegs];
  }
  const maxOrder = daySegs.reduce((m,s)=>Math.max(m,Number(s.sort_order)||100),100);
  const newSeg = {day:Number(day),sort_order:maxOrder+100,status_code:status,status,start_time:minutesToTime(startMin),end_time:endMin>=1439?'23:59':minutesToTime(endMin),note};
  // Clamp the previous segment so the timeline stays contiguous (rest is never erased).
  if(daySegs.length){
    const prev=daySegs[daySegs.length-1];
    const prevEnd = prev.end_time==='23:59'?1440:timeToMinutes(prev.end_time);
    if(prevEnd>startMin){
      prev.end_time = startMin<=0 ? '00:00' : minutesToTime(Math.max(0,startMin));
    }
  }
  return [...otherSegs,...daySegs,newSeg];
}

function getDaySegments(item, day){
  const segments = Array.isArray(item?.status_segments) ? item.status_segments.filter(x => x && Number(x.day) === Number(day)) : [];
  if(!segments.length){
    const code = item?.monthly?.[`d${day}`] || '';
    return code ? [{day,sort_order:100,status_code:code,status:code,start_time:'00:00',end_time:'23:59',note:STATUS_META[code]?.label||code}] : [];
  }
  const normalized = segments
    .map((seg,index)=>normalizeDaySegment(seg, day, index))
    .sort((a,b)=>(a.sort_order||100)-(b.sort_order||100)||timeToMinutes(a.start_time)-timeToMinutes(b.start_time));
  // Old records saved Resting as a full 24-hour status. Display the actual
  // rest window when a rest start time exists, without changing historic data.
  if(normalized.length===1 && normalized[0].status_code==='R' && normalized[0].start_time==='00:00' && normalized[0].end_time==='23:59' && item?.restStarted){
    const started=new Date(item.restStarted);
    if(!isNaN(started.getTime()) && started.getDate()===Number(day) && started.getMonth()===CM && started.getFullYear()===CY){
      normalized[0].start_time=fmtTime(started);
      normalized[0].end_time=minutesToTime(Math.min(1439,timeToMinutes(normalized[0].start_time)+getRestHours(item)*60));
    }
  }
  return normalized;
}

function getFinalStatusForDay(item, day){
  const segments = getDaySegments(item, day);
  if (segments.length) {
    return segments[segments.length - 1].status_code || segments[segments.length - 1].status || '';
  }
  return item?.monthly?.[`d${day}`] || '';
}

function timeToMinutes(value){
  const match=String(value||'').match(/^(\d{1,2}):(\d{2})$/);
  if(!match) return 0;
  return Math.max(0,Math.min(1439,Number(match[1])*60+Number(match[2])));
}

function minutesToTime(minutes){
  const safe=Math.max(0,Math.min(1439,Number(minutes)||0));
  return `${String(Math.floor(safe/60)).padStart(2,'0')}:${String(safe%60).padStart(2,'0')}`;
}

function normalizeDaySegment(seg, day, index=0){
  const code=seg.status_code||seg.status||'';
  return {
    ...seg,
    day:Number(seg.day||day),
    sort_order:Number(seg.sort_order||((index+1)*100)),
    status_code:code,
    status:seg.status||code,
    start_time:seg.start_time||seg.start||'00:00',
    end_time:seg.end_time||seg.end||'23:59',
    note:seg.note||seg.notes||STATUS_META[code]?.label||code,
  };
}

function segmentDurationMinutes(seg){
  const start=timeToMinutes(seg.start_time);
  const end=seg.end_time==='23:59'?1440:timeToMinutes(seg.end_time);
  return Math.max(1,end-start);
}

function buildStatusSegmentsForDayList(item, day, daySegments){
  const existing = Array.isArray(item?.status_segments) ? item.status_segments.filter(seg=>Number(seg?.day)!==Number(day)) : [];
  const cleaned = (daySegments||[])
    .map((seg,index)=>normalizeDaySegment(seg, day, index))
    .filter(seg=>seg.status_code)
    .sort((a,b)=>timeToMinutes(a.start_time)-timeToMinutes(b.start_time))
    .map((seg,index)=>({
      day:Number(day),
      sort_order:(index+1)*100,
      status_code:seg.status_code,
      status:seg.status_code,
      start_time:seg.start_time,
      end_time:seg.end_time,
      note:seg.note||STATUS_META[seg.status_code]?.label||seg.status_code,
    }));
  return [...existing,...cleaned];
}

function renderMonthDayCell(segments, code){
  if(!segments.length) return '';
  if(segments.length===1) return `<span class="month-status-code">${code||''}</span>`;
  const title=segments.map(seg=>`${seg.status_code} ${seg.start_time}-${seg.end_time}`).join(' -> ');
  return `<span class="month-segment-stack" title="${title}">${segments.map(seg=>{
    const status=STATUS_META[seg.status_code]||{};
    const pct=Math.max(8,Math.round((segmentDurationMinutes(seg)/1440)*100));
    return `<span class="month-segment-slice" style="flex-basis:${pct}%;background:${status.bg||'#CBD5E1'};border-color:${status.fg||'#64748B'}"></span>`;
  }).join('')}</span>`;
}

function statusBadgeHtml(code, extraClass=''){
  const meta=STATUS_META[code]||{label:code,bg:'#ECEFF1',fg:'#37474F'};
  return `<span class="${extraClass}" style="background:${meta.bg};color:${meta.fg};border-color:${meta.fg}55">${code}</span>`;
}

function ensureDaySegmentEditor(){
  let editor=document.getElementById('daySegmentEditor');
  if(editor) return editor;
  const notes=document.getElementById('mNotes');
  if(!notes) return null;
  editor=document.createElement('div');
  editor.id='daySegmentEditor';
  editor.className='day-segment-editor';
  editor.style.display='none';
  editor.innerHTML=`
    <div class="segment-editor-head">
      <div>
        <label style="margin:0">Daily status segments</label>
        <div class="segment-editor-note">Add each same-day status in order. The final segment becomes the monthly code.</div>
      </div>
      <button type="button" class="btn btn-ghost btn-sm" onclick="addDaySegmentRow()">Add segment</button>
    </div>
    <div id="daySegmentRows" class="day-segment-rows"></div>`;
  notes.parentNode.insertBefore(editor, notes);
  return editor;
}

function setDaySegmentEditorVisible(visible){
  const editor=ensureDaySegmentEditor();
  if(editor) editor.style.display=visible?'block':'none';
}

function addDaySegmentRow(segment={}){
  const rows=document.getElementById('daySegmentRows');
  if(!rows) return;
  const index=rows.children.length;
  const code=segment.status_code||segment.status||document.getElementById('mStatus')?.value||'SB';
  const start=segment.start_time||segment.start||minutesToTime(Math.min(index*480, 1439));
  const end=segment.end_time||segment.end||(index===0?'23:59':'23:59');
  const note=String(segment.note||segment.notes||STATUS_META[code]?.label||code).replace(/"/g,'&quot;');
  const row=document.createElement('div');
  row.className='day-segment-row';
  row.innerHTML=`
    <select class="day-seg-status" onchange="syncPrimaryStatusFromSegments();onStatusChange()">${buildStatusOptions(code)}</select>
    <input class="day-seg-start" type="time" value="${start}">
    <input class="day-seg-end" type="time" value="${end}">
    <input class="day-seg-note" type="text" value="${note}" placeholder="Duty note">
    <button type="button" class="day-seg-remove" title="Remove segment" onclick="removeDaySegmentRow(this)">x</button>`;
  rows.appendChild(row);
  syncPrimaryStatusFromSegments();
}

function removeDaySegmentRow(button){
  const row=button?.closest('.day-segment-row');
  if(row) row.remove();
  syncPrimaryStatusFromSegments();
}

function syncPrimaryStatusFromSegments(){
  const rows=[...document.querySelectorAll('#daySegmentRows .day-segment-row')];
  const last=rows[rows.length-1]?.querySelector('.day-seg-status')?.value;
  const statusEl=document.getElementById('mStatus');
  if(last && statusEl) statusEl.value=last;
}

function loadDaySegmentEditor(segments){
  const editor=ensureDaySegmentEditor();
  if(!editor) return;
  const rows=document.getElementById('daySegmentRows');
  rows.innerHTML='';
  (segments.length?segments:[{status_code:document.getElementById('mStatus')?.value||'SB',start_time:'00:00',end_time:'23:59'}]).forEach(seg=>addDaySegmentRow(seg));
  setDaySegmentEditorVisible(true);
}

function readDaySegmentEditor(day){
  const rows=[...document.querySelectorAll('#daySegmentRows .day-segment-row')];
  return rows.map((row,index)=>({
    day,
    sort_order:(index+1)*100,
    status_code:row.querySelector('.day-seg-status')?.value||'',
    start_time:row.querySelector('.day-seg-start')?.value||'00:00',
    end_time:row.querySelector('.day-seg-end')?.value||'23:59',
    note:row.querySelector('.day-seg-note')?.value||'',
  })).filter(seg=>seg.status_code);
}
function windowLabel(windowKey){
  switch(windowKey){
    case 'month': return 'This month';
    case 'quarter': return 'Last quarter';
    case '90d': return 'Last 90 days';
    case '180d': return 'Last 180 days';
    case '365d': return 'Last 365 days';
    default: return windowKey;
  }
}
function getWindowMonthKeys(windowKey){
  switch(windowKey){
    case 'quarter': return getRecentMonthKeys(3);
    case '90d': return getRecentMonthKeys(3);
    case '180d': return getRecentMonthKeys(6);
    case '365d': return getRecentMonthKeys(12);
    default: return [MONTH_KEY];
  }
}
async function fetchCrewByMonth(monthKey){
  const depots = currentUser.isHQ ? getActiveDepots() : [currentUser.depot];
  // For current month, prefer server-side fetch when backend is available to avoid stale/empty in-memory state.
  if (monthKey === MONTH_KEY) {
    if (db) {
      const list = [];
      for (const depot of depots) {
        try {
          const snap = await getDocs(query(collection(db, 'crew'), where('depot', '==', depot), where('monthKey', '==', monthKey)));
          snap.forEach(docSnap => { list.push(docSnap.data()); });
        } catch (err) {
          console.warn('fetchCrewByMonth server fetch failed for depot', depot, err && err.message);
        }
      }
      // If server returned results, use them; otherwise fall back to in-memory state.
      if (list.length) return list;
      return getAllCrew(state, depots);
    }
    return getAllCrew(state, depots);
  }

  if (!db) return [];
  const list = [];
  for (const depot of depots) {
    const snap = await getDocs(query(collection(db, 'crew'), where('depot', '==', depot), where('monthKey', '==', monthKey)));
    snap.forEach(docSnap => { list.push(docSnap.data()); });
  }
  return list;
  await ensureCoreAccessUsers();
}
async function exportMonthlyCSV(monthKey=MONTH_KEY){
  const crew = await fetchCrewByMonth(monthKey);
  if(!crew.length){
    alert(`No monthly register found for ${formatMonthLabel(monthKey)}.`);
    return;
  }
  const maxDay = monthKey===MONTH_KEY ? CD : Math.max(...crew.map(c=>Object.keys(buildMonthlyFromStatusSegments(c)).length), 0);
  let hdr='ID,Name,Designation,Depot';
  for(let i=1;i<=maxDay;i++) hdr+=`,${i}`;
  const statusCols = getStatusCodes();
  hdr += ',' + statusCols.join(',') + '\n';
  let csv = hdr;
  crew.sort((a,b)=>a.name.localeCompare(b.name)).forEach(c=>{
    let row=`"${c.id}","${c.name}","${getDesignationLabel(c.grade)}","${c.depot}"`;
    const counts = Object.fromEntries(statusCols.map(s => [s, 0]));
    for(let i=1;i<=maxDay;i++){
      const code=getDaySegmentCode(c,i)||'';
      if(Object.prototype.hasOwnProperty.call(counts, code)) counts[code]++;
      row+=`,"${code}"`;
    }
    // append counts in configured order
    row += ',' + statusCols.map(s => counts[s]||0).join(',') + '\n';
    csv+=row;
  });
  dlCSV(csv,`KR_Monthly_${monthKey.replace('-','_')}.csv`);
}
async function fetchUtilizationReportData(windowKey='month'){
  const monthKeys=getWindowMonthKeys(windowKey);
  const crewMap=new Map();
  const totalDays=monthKeys.reduce((sum,key)=>sum+getDaysInMonthKey(key),0);
  if(!db && windowKey!=='month') return {rows:[],totalDays,monthKeys};
  for(const monthKey of monthKeys){
    const crewList = await fetchCrewByMonth(monthKey);
    crewList.forEach(c=>{
      const idKey=`${c.depot}_${c.id}`;
      const existing = crewMap.get(idKey) || {id:c.id,name:c.name,grade:getDesignationLabel(c.grade),depot:c.depot,bookedDays:0,months:new Set()};
      existing.months.add(monthKey);
      const days=getDaysInMonthKey(monthKey);
      for(let d=1;d<=days;d++) if(getDaySegmentCode(c,d)==='BK') existing.bookedDays++;
      crewMap.set(idKey,existing);
    });
  }
  const rows=Array.from(crewMap.values()).map(r=>({
    id:r.id,name:r.name,grade:r.grade,depot:r.depot,bookedDays:r.bookedDays,monthKeys:Array.from(r.months).sort(),totalDays
  }));
  return {rows,totalDays,monthKeys};
}
async function exportUtilizationCSV(windowKey='month'){
  const data = await fetchUtilizationReportData(windowKey);
  if(!data.rows.length){
    alert(`No utilization data available for ${windowLabel(windowKey)}.`);
    return;
  }
  let csv='ID,Name,Designation,Depot,Booked Days,Available Days,Utilization %\n';
  data.rows.sort((a,b)=>b.bookedDays-a.bookedDays||a.name.localeCompare(b.name)).forEach(r=>{
    const util = data.totalDays?((r.bookedDays/data.totalDays)*100).toFixed(1):'0.0';
    csv+=`"${r.id}","${r.name}","${r.grade}","${r.depot}",${r.bookedDays},${data.totalDays},${util}\n`;
  });
  dlCSV(csv,`KR_Utilization_${windowKey}_${todayStr()}.csv`);
}
async function updateReportSummary(){
  const monthKey=document.getElementById('reportMonth')?.value||MONTH_KEY;
  const windowKey=document.getElementById('utilWindow')?.value||'month';
  const summaryEl=document.getElementById('utilSummary');
  if(!summaryEl) return;
  const monthCrew = await fetchCrewByMonth(monthKey);
  let monthNote;
  if(!monthCrew.length){
    monthNote=`No archive found for ${formatMonthLabel(monthKey)}.`;
  } else {
    const bookedDays = monthCrew.reduce((sum,c)=>{
      for(let d=1;d<=getDaysInMonthKey(monthKey);d++) if(getDaySegmentCode(c,d)==='BK') sum++;
      return sum;
    },0);
    monthNote=`${formatMonthLabel(monthKey)}: ${monthCrew.length} crew, ${bookedDays} booked days.`;
  }
  const utilData = await fetchUtilizationReportData(windowKey);
  let utilNote;
  if(!utilData.rows.length){
    utilNote=`No utilization data available for ${windowLabel(windowKey)}.`;
  } else {
    const totalBooked = utilData.rows.reduce((sum,r)=>sum+r.bookedDays,0);
    const top = utilData.rows.slice().sort((a,b)=>b.bookedDays-a.bookedDays).slice(0,3).map(r=>`${r.name} (${r.bookedDays})`).join(', ');
    utilNote=`${windowLabel(windowKey)}: ${utilData.rows.length} crew, ${totalBooked} booked days, top ${top}.`;
  }
  summaryEl.textContent = `${monthNote} ${utilNote}`;
}

async function reloadAdminData(){
  await seedDepotMetaIfEmpty();
  await loadDepotMeta();
  await seedDesignationMetaIfEmpty();
  await loadDesignationMeta();
  await seedStatusMetaIfEmpty();
  await loadStatusMeta();
  await seedReportMetaIfEmpty();
  await loadReportMeta();
  await seedTrainTypeMetaIfEmpty();
  await loadTrainTypeMeta();
  await seedShiftMetaIfEmpty();
  await loadShiftMeta();
  await loadAccessMeta();
  renderAdmin();
}

async function loadAdminUsers(){
  if(!db){
    try{
      const data = await fetchLocalAdminMeta();
      const users = (data.users||[]).map(u=>({username:u.username||u.id||'',...u}));
      users.sort((a,b)=>String(a.username).localeCompare(String(b.username)));
      setUserMetadata(users);
      return users;
    }catch(err){
      console.error('Failed to load local admin users',err);
      return [];
    }
  }
  const snap=await getDocs(collection(db,'users'));
  const users=[];
  snap.forEach(docSnap=>{users.push({username:docSnap.id,...docSnap.data()});});
  users.sort((a,b)=>String(a.username).localeCompare(String(b.username)));
  setUserMetadata(users);
  return users;
}

function normalizeAccessMetaRecord(docSnapOrData){
  const data = docSnapOrData?.data ? docSnapOrData.data() : docSnapOrData;
  const metadata = data?.metadata || {};
  const id = String(data?.id || docSnapOrData?.id || docSnapOrData?.record_id || '').trim();
  if(!id) return null;
  return {
    id,
    label:String(data?.label || data?.name || id).trim() || id,
    description:String(data?.description || '').trim(),
    active:data?.active !== false && metadata?.active !== false,
    system:!!data?.system || !!data?.is_system || !!data?.isSystem || !!metadata?.system,
    canLogin: data?.canLogin ?? data?.can_login ?? metadata?.can_login ?? metadata?.canLogin ?? false,
    isCrewMember: data?.isCrewMember ?? data?.is_crew_member ?? metadata?.is_crew_member ?? metadata?.isCrewMember ?? false,
    isUser: data?.isUser ?? data?.is_user ?? metadata?.is_user ?? metadata?.isUser ?? false,
    permissions: Array.isArray(data?.permissions) ? data.permissions : [],
  };
}

function sortAccessMetaRecords(records){
  return (records || []).filter(Boolean).sort((a,b)=>String(a.label||a.id).localeCompare(String(b.label||b.id)) || String(a.id).localeCompare(String(b.id)));
}

function getAccessMetaRecords(key){
  return key==='roles' ? sortAccessMetaRecords(roleMetadataCache) : sortAccessMetaRecords(permissionMetadataCache);
}

function setAccessMetaRecords(key, records){
  const next = sortAccessMetaRecords(records);
  if(key==='roles'){
    setRoleMetadata(next);
  } else {
    setPermissionMetadata(next);
  }
}

async function loadAccessMeta(){
  if(!db){
    setAccessMetaRecords('roles', []);
    setAccessMetaRecords('permissions', []);
    return;
  }
  try{
    const roleSnap = await getDocs(collection(db,'roles'));
    const roles=[];
    roleSnap.forEach(docSnap=>{ const meta=normalizeAccessMetaRecord(docSnap); if(meta) roles.push(meta); });
    setAccessMetaRecords('roles', roles);

    const permissionSnap = await getDocs(collection(db,'permissions'));
    const permissions=[];
    permissionSnap.forEach(docSnap=>{ const meta=normalizeAccessMetaRecord(docSnap); if(meta) permissions.push(meta); });
    setAccessMetaRecords('permissions', permissions);
  }catch(err){
    console.error('Failed to load access metadata', err);
    setAccessMetaRecords('roles', []);
    setAccessMetaRecords('permissions', []);
  }
}

function buildAccessMetaSection(key, records){
  const config = getAccessMetaConfig(key);
  const title = config?.title || key;
  const singular = title.endsWith('s') ? title.slice(0, -1) : title;
  const rows = sortAccessMetaRecords(records).map(meta=>{
    const locked=key==='roles'&&meta.id==='super_admin';
    const disabled=locked?'disabled':'';
    return `
    <div class="admin-row" style="grid-template-columns:minmax(140px,1fr) minmax(170px,1.1fr) 90px 90px 90px 90px 140px 120px 90px;">
      <input value="${meta.id}" disabled style="padding:7px 9px;border:1px solid var(--border);border-radius:var(--r);background:#F7F9FC" placeholder="${singular} code">
      <input value="${meta.label}" data-admin-${key}-label="${meta.id}" class="admin-field" placeholder="${singular} name" ${disabled}>
      <label class="admin-checkbox-label"><input type="checkbox" ${meta.active!==false?'checked':''} data-admin-${key}-active="${meta.id}" ${disabled}> Active</label>
      <label class="admin-checkbox-label"><input type="checkbox" ${meta.canLogin!==false?'checked':''} data-admin-${key}-login="${meta.id}" ${disabled}> Login</label>
      <label class="admin-checkbox-label"><input type="checkbox" ${meta.isCrewMember?'checked':''} data-admin-${key}-crew="${meta.id}" ${disabled}> Crew</label>
      <label class="admin-checkbox-label"><input type="checkbox" ${meta.isUser?'checked':''} data-admin-${key}-user="${meta.id}" ${disabled}> User</label>
      ${key==='roles'?`<input value="${(meta.permissions||[]).join(', ')}" data-admin-${key}-permissions="${meta.id}" class="admin-field" placeholder="Permissions (comma-separated)" ${disabled}>`:'<div></div>'}
      <input value="${meta.description||''}" data-admin-${key}-description="${meta.id}" class="admin-field" placeholder="Description" ${disabled}>
      <div style="display:flex;gap:6px;justify-content:flex-end">
        ${locked?'<span style="font-size:11px;color:var(--text2)">Full access (locked)</span>':`<button class="btn btn-primary btn-sm" onclick="saveAccessMetaRecord('${key}','${meta.id}')">Save</button><button class="btn btn-danger btn-sm" onclick="removeAccessMetaRecord('${key}','${meta.id}')">Delete</button>`}
      </div>
    </div>`;
  }).join('');
  const newRow = `
    <div class="admin-row admin-row-add" style="grid-template-columns:minmax(140px,1fr) minmax(170px,1.1fr) 90px 90px 90px 90px 120px 90px;">
      <input value="" data-admin-${key}-code="new" class="admin-field" placeholder="New ${singular.toLowerCase()} code">
      <input value="" data-admin-${key}-label="new" class="admin-field" placeholder="${singular} name">
      <label class="admin-checkbox-label"><input type="checkbox" checked data-admin-${key}-active="new"> Active</label>
      <label class="admin-checkbox-label"><input type="checkbox" checked data-admin-${key}-login="new"> Login</label>
      <label class="admin-checkbox-label"><input type="checkbox" checked data-admin-${key}-crew="new"> Crew</label>
      <label class="admin-checkbox-label"><input type="checkbox" checked data-admin-${key}-user="new"> User</label>
      <input value="" data-admin-${key}-description="new" class="admin-field" placeholder="Description">
      <div style="display:flex;gap:6px;justify-content:flex-end">
        <button class="btn btn-primary btn-sm" onclick="saveAccessMetaRecord('${key}','new')">Add</button>
        <button class="btn btn-ghost btn-sm" disabled>Delete</button>
      </div>
    </div>
  `;
  return `
    <div class="admin-card">
      <div class="admin-section-header">
        <div>
          <div class="admin-section-title">${title}</div>
          <p class="admin-section-note">${config?.note || ''}</p>
        </div>
      </div>
      <div class="admin-row-header" style="grid-template-columns:minmax(140px,1fr) minmax(170px,1.1fr) 90px 90px 90px 90px 140px 120px 90px;">
        <div>Code</div><div>Name</div><div>Active</div><div>Login</div><div>Crew</div><div>User</div><div>Permissions</div><div>Description</div><div></div>
      </div>
      ${rows}${newRow}
    </div>
  `;
}

async function saveAccessMetaRecord(key, recordId){
  const config = getAccessMetaConfig(key);
  if(!config) return;
  const codeSelector = `[data-admin-${key}-code="${recordId}"]`;
  const labelSelector = `[data-admin-${key}-label="${recordId}"]`;
  const descSelector = `[data-admin-${key}-description="${recordId}"]`;
  const activeSelector = `[data-admin-${key}-active="${recordId}"]`;
  const loginSelector = `[data-admin-${key}-login="${recordId}"]`;
  const crewSelector = `[data-admin-${key}-crew="${recordId}"]`;
  const userSelector = `[data-admin-${key}-user="${recordId}"]`;
  const codeEl = document.querySelector(codeSelector);
  const labelEl = document.querySelector(labelSelector);
  const descEl = document.querySelector(descSelector);
  const activeEl = document.querySelector(activeSelector);
  const loginEl = document.querySelector(loginSelector);
  const crewEl = document.querySelector(crewSelector);
  const userEl = document.querySelector(userSelector);
  const permissionsEl = document.querySelector(`[data-admin-${key}-permissions="${recordId}"]`);
  const nextId = recordId === 'new' ? (codeEl?.value || '').trim() : recordId;
  if(!nextId){
    alert(`${config.title.slice(0, -1)} code cannot be blank`);
    return;
  }
  const payload = {
    id: nextId,
    label:(labelEl?.value || nextId).trim(),
    description:(descEl?.value || '').trim(),
    active:!!activeEl?.checked,
    canLogin:!!loginEl?.checked,
    isCrewMember:!!crewEl?.checked,
    isUser:!!userEl?.checked,
    permissions:String(permissionsEl?.value||'').split(',').map(item=>item.trim()).filter(Boolean),
    system:false,
  };
  try{
    if(!db){
      await saveLocalAdminRecord(config.collection, nextId, payload);
      await loadAccessMeta();
      setSyncStatus('ok','Local access metadata saved');
    } else {
      await setDoc(doc(db, config.collection, nextId), payload, {merge:true});
      await loadAccessMeta();
    }
    renderAdmin();
  }catch(err){
    console.error(`Failed to save ${config.collection}`, err);
    setSyncStatus('err','Save failed');
    alert(`Unable to save ${config.title.toLowerCase()} right now.`);
  }
}

async function removeAccessMetaRecord(key, recordId){
  const config = getAccessMetaConfig(key);
  if(!config) return;
  if(!confirm(`Remove ${recordId} from ${config.title}?`)) return;
  try{
    if(!db){
      await deleteLocalAdminRecord(config.collection, recordId);
      await loadAccessMeta();
      setSyncStatus('ok','Local access metadata removed');
    } else {
      await deleteDoc(doc(db, config.collection, recordId));
      await loadAccessMeta();
    }
    renderAdmin();
  }catch(err){
    console.error(`Failed to delete ${config.collection}`, err);
    setSyncStatus('err','Delete failed');
    alert(`Unable to remove ${config.title.toLowerCase()} right now.`);
  }
}

function buildSimpleMetaSection(key, records){
  const config = getSimpleMetaConfig(key);
  const title = config?.title || key;
  const singular = title.endsWith('s') ? title.slice(0, -1) : title;
  const rows = sortSimpleMetaRecords(records).map(meta=>`
    <div class="admin-row" style="grid-template-columns:minmax(180px,1.4fr) 120px 90px 90px;">
      <input value="${meta.id}" disabled style="padding:7px 9px;border:1px solid var(--border);border-radius:var(--r);background:#F7F9FC" placeholder="${singular} id">
      <input type="number" value="${meta.order||999}" data-admin-${key}-order="${meta.id}" class="admin-field" min="0" placeholder="Order">
      <button class="btn btn-primary btn-sm" onclick="saveSimpleMetaRecord('${key}','${meta.id}')">Save</button>
      <button class="btn btn-danger btn-sm" onclick="removeSimpleMetaRecord('${key}','${meta.id}')">Delete</button>
    </div>
  `).join('');
  const newRow = `
    <div class="admin-row admin-row-add" style="grid-template-columns:minmax(180px,1.4fr) 120px 90px 90px;">
      <input value="" data-admin-${key}-id="new" class="admin-field" placeholder="New ${singular.toLowerCase()}">
      <input type="number" value="999" data-admin-${key}-order="new" class="admin-field" min="0" placeholder="Order">
      <button class="btn btn-primary btn-sm" onclick="saveSimpleMetaRecord('${key}','new')">Add</button>
      <button class="btn btn-ghost btn-sm" disabled>Delete</button>
    </div>
  `;
  return `
    <div class="admin-card">
      <div class="admin-section-header">
        <div>
          <div class="admin-section-title">${title}</div>
          <p class="admin-section-note">${config?.note || ''}</p>
        </div>
      </div>
      <div class="admin-row-header" style="grid-template-columns:minmax(180px,1.4fr) 120px 90px 90px;">
        <div>ID</div><div>Order</div><div></div><div></div>
      </div>
      ${rows}${newRow}
    </div>
  `;
}

async function refreshSimpleMetaUi(key){
  await loadSimpleMeta(key);
  if(key==='trainType') populateTrainTypeSelect();
  if(key==='shift') populateShiftSelects();
}

async function saveSimpleMetaRecord(key, recordId){
  const config = getSimpleMetaConfig(key);
  if(!config) return;
  const idSelector = `[data-admin-${key}-id="${recordId}"]`;
  const orderSelector = `[data-admin-${key}-order="${recordId}"]`;
  const idEl = document.querySelector(idSelector);
  const orderEl = document.querySelector(orderSelector);
  const nextId = recordId === 'new' ? (idEl?.value || '').trim() : recordId;
  if(!nextId){
    alert(`${config.title.slice(0, -1)} id cannot be blank`);
    return;
  }
  const payload = {
    id: nextId,
    label: nextId,
    order: Number(orderEl?.value || 999),
    active: true,
  };
  try{
    if(!db){
      await saveLocalAdminRecord(config.collection, nextId, payload);
      await refreshSimpleMetaUi(key);
      setSyncStatus('ok','Local metadata saved');
    } else {
      await setDoc(doc(db, config.collection, nextId), payload, {merge:true});
      await refreshSimpleMetaUi(key);
    }
    renderAdmin();
  }catch(err){
    console.error(`Failed to save ${config.collection}`, err);
    setSyncStatus('err','Save failed');
    alert(`Unable to save ${config.title.toLowerCase()} right now.`);
  }
}

async function removeSimpleMetaRecord(key, recordId){
  const config = getSimpleMetaConfig(key);
  if(!config) return;
  if(!confirm(`Remove ${recordId} from ${config.title}?`)) return;
  try{
    if(!db){
      await deleteLocalAdminRecord(config.collection, recordId);
      await refreshSimpleMetaUi(key);
      setSyncStatus('ok','Local metadata removed');
    } else {
      await deleteDoc(doc(db, config.collection, recordId));
      await refreshSimpleMetaUi(key);
    }
    renderAdmin();
  }catch(err){
    console.error(`Failed to delete ${config.collection}`, err);
    setSyncStatus('err','Delete failed');
    alert(`Unable to remove ${config.title.toLowerCase()} right now.`);
  }
}

async function renderAdmin(){
  const dbReady = !!db;
  const pbody = document.getElementById('pbody');
  const errorPanel = '<div style="background:#fff;border:1px solid var(--border);border-radius:var(--r);padding:18px;color:var(--text2)">Unable to render admin interface. Check console for details.</div>';
  safeSetText('phSub', 'Manage MySQL-backed configuration');
  safeSetInner('phActions', hasGlobalAccess()?'<button class="btn btn-ghost btn-sm no-print" onclick="reloadAdminData()">Reload</button>':'');
  if(!hasGlobalAccess()){
    if(pbody) pbody.innerHTML='<div style="background:#fff;border:1px solid var(--border);border-radius:var(--r);padding:18px;color:var(--text2)">Admin tools are available to HQ users only.</div>';
    return;
  }
  if(pbody) pbody.innerHTML='<div style="background:#fff;border:1px solid var(--border);border-radius:var(--r);padding:18px;color:var(--text2)">Loading admin data…</div>';
  let users=[];
  try{
    users = await loadAdminUsers();
    await loadDepotMeta();
    await loadDesignationMeta();
    await loadStatusMeta();
    await loadReportMeta();
    await loadTrainTypeMeta();
    await loadShiftMeta();
    await loadAccessMeta();
  }catch(err){
    console.error('Failed to load admin users',err);
    if(pbody) pbody.innerHTML=`<div style="background:#fff;border:1px solid var(--border);border-radius:var(--r);padding:18px;color:var(--text2)">Unable to load admin data. Check your MySQL backend connection.</div>`;
    return;
  }
  try{
    const activeDepotMeta = getAllDepotMetadata();
  const depotRecords = activeDepotMeta.length ? activeDepotMeta : getActiveDepots().map(depot=>({id:depot,label:depot,color:DEPOT_COLORS[depot]||'#37474F',restHours:REST_HOURS[depot]||12,active:true,order:getActiveDepots().indexOf(depot)+1}));
  const depotRows = depotRecords.map(meta=>{
    const active = meta.active !== false;
    return `<div class="admin-row">
      <input value="${meta.id}" data-admin-depot-id="${meta.id}" class="admin-field" placeholder="Depot id">
      <input value="${meta.label}" data-admin-depot-label="${meta.id}" class="admin-field" placeholder="Label">
      <input type="color" value="${meta.color}" data-admin-depot-color="${meta.id}" class="admin-field" style="width:60px;height:40px;padding:2px;cursor:pointer;">
      <input type="number" value="${meta.restHours}" min="1" data-admin-depot-hours="${meta.id}" class="admin-field" placeholder="Hours">
      <label class="admin-checkbox-label"><input type="checkbox" ${active?' checked':''} data-admin-depot-active="${meta.id}"> Active</label>
      <button class="btn btn-primary btn-sm" onclick="saveDepotMetaRecord('${meta.id}')">Save</button>
      <button class="btn btn-ghost btn-sm" onclick="deleteDepotMetaRecord('${meta.id}')">Delete</button>
    </div>`;
  }).join('');

  const newDepotRow = `<div class="admin-row admin-row-add">
      <input value="" data-admin-depot-id="newDepot" class="admin-field" placeholder="New depot id">
      <input value="" data-admin-depot-label="newDepot" class="admin-field" placeholder="Label">
      <input type="color" value="#37474F" data-admin-depot-color="newDepot" class="admin-field" style="width:60px;height:40px;padding:2px;cursor:pointer;">
      <input type="number" value="12" min="1" data-admin-depot-hours="newDepot" class="admin-field" placeholder="Hours">
      <label class="admin-checkbox-label"><input type="checkbox" data-admin-depot-active="newDepot"> Active</label>
      <button class="btn btn-primary btn-sm" onclick="saveDepotMetaRecord('newDepot')">Add depot</button>
    </div>`;

  const designationRecords = getAllDesignationMetadata().length ? getAllDesignationMetadata() : DEFAULT_DESIGNATION_DEFINITIONS;
  const designationRows = designationRecords.sort((a,b)=>(a.order||999)-(b.order||999)||String(a.label||a.id).localeCompare(String(b.label||b.id))).map(meta=>{
    const roomFlag = meta.runningRoomEligible!==undefined?meta.runningRoomEligible:meta.restEligible!==false;
    return `<div class="admin-row" style="grid-template-columns:minmax(140px,1fr) minmax(180px,1.2fr) 80px 80px 80px 80px 80px 100px 90px;">
      <input value="${meta.id}" data-admin-desig-id="${meta.id}" class="admin-field" placeholder="Designation id" disabled>
      <input value="${meta.label}" data-admin-desig-label="${meta.id}" class="admin-field" placeholder="Label" disabled>
      <label class="admin-checkbox-label"><input type="checkbox" ${meta.restEligible!==false?'checked':''} data-admin-desig-rest="${meta.id}"> Rest</label>
      <label class="admin-checkbox-label"><input type="checkbox" ${roomFlag?'checked':''} data-admin-desig-room="${meta.id}"> Room</label>
      <label class="admin-checkbox-label"><input type="checkbox" ${meta.canLogin!==false?'checked':''} data-admin-desig-login="${meta.id}"> Login</label>
      <label class="admin-checkbox-label"><input type="checkbox" ${meta.isCrewMember?'checked':''} data-admin-desig-crew="${meta.id}"> Crew</label>
      <label class="admin-checkbox-label"><input type="checkbox" ${meta.isUser?'checked':''} data-admin-desig-user="${meta.id}"> User</label>
      <input type="number" value="${meta.order||999}" data-admin-desig-order="${meta.id}" class="admin-field" min="0" placeholder="Order">
      <div style="display:flex;gap:6px;justify-content:flex-end">
        <button class="btn btn-primary btn-sm" onclick="saveDesignationMetaRecord('${meta.id}')">Save</button>
        <button class="btn btn-danger btn-sm" onclick="deleteDesignationMetaRecord('${meta.id}')">Delete</button>
      </div>
    </div>`;
  }).join('');
  const newDesignationRow = `<div class="admin-row admin-row-add" style="grid-template-columns:minmax(140px,1fr) minmax(180px,1.2fr) 80px 80px 80px 80px 80px 100px 90px;">
      <input value="" data-admin-desig-id="new" class="admin-field" placeholder="New designation id">
      <input value="" data-admin-desig-label="new" class="admin-field" placeholder="Label">
      <label class="admin-checkbox-label"><input type="checkbox" checked data-admin-desig-rest="new"> Rest</label>
      <label class="admin-checkbox-label"><input type="checkbox" checked data-admin-desig-room="new"> Room</label>
      <label class="admin-checkbox-label"><input type="checkbox" checked data-admin-desig-login="new"> Login</label>
      <label class="admin-checkbox-label"><input type="checkbox" checked data-admin-desig-crew="new"> Crew</label>
      <label class="admin-checkbox-label"><input type="checkbox" checked data-admin-desig-user="new"> User</label>
      <input type="number" value="999" data-admin-desig-order="new" class="admin-field" min="0" placeholder="Order">
      <button class="btn btn-primary btn-sm" onclick="saveDesignationMetaRecord('new')">Add</button>
    </div>`;

  const statusRecords = Object.values(statusMetadataCache).length ? Object.values(statusMetadataCache) : STATUSES.map(statusId => ({id: statusId, label: STATUS_META[statusId]?.label || statusId, bg: STATUS_META[statusId]?.bg || '#ECEFF1', fg: STATUS_META[statusId]?.fg || '#37474F', order: STATUS_META[statusId]?.order || 999, active: true}));
  const statusRows = statusRecords.sort((a,b)=>(a.order||999)-(b.order||999)||String(a.label||a.id).localeCompare(String(b.label||b.id))).map(meta=>{
    return `<div class="admin-row">
      <input value="${meta.id}" disabled style="padding:7px 9px;border:1px solid var(--border);border-radius:var(--r);background:#F7F9FC" placeholder="Status id">
      <input value="${meta.label}" data-admin-status-label="${meta.id}" style="padding:7px 9px;border:1px solid var(--border);border-radius:var(--r)" placeholder="Label">
      <input type="color" value="${meta.bg||'#ECEFF1'}" data-admin-status-bg="${meta.id}" style="width:60px;height:40px;padding:2px;cursor:pointer;border:1px solid var(--border);border-radius:var(--r);">
      <input type="color" value="${meta.fg||'#37474F'}" data-admin-status-fg="${meta.id}" style="width:60px;height:40px;padding:2px;cursor:pointer;border:1px solid var(--border);border-radius:var(--r);">
      <div style="display:flex;gap:6px;justify-content:flex-end">
        <button class="btn btn-primary btn-sm" onclick="saveStatusMetaRecord('${meta.id}')">Save</button>
        <button class="btn btn-danger btn-sm" onclick="deleteStatusMetaRecord('${meta.id}')">Delete</button>
      </div>
    </div>`;
  }).join('');
  const newStatusRow = `<div class="admin-row admin-row-add">
      <input value="" data-admin-status-id="new" style="padding:7px 9px;border:1px solid var(--border);border-radius:var(--r);" placeholder="New status id">
      <input value="" data-admin-status-label="new" style="padding:7px 9px;border:1px solid var(--border);border-radius:var(--r)" placeholder="Label">
      <input type="color" value="#ECEFF1" data-admin-status-bg="new" style="width:60px;height:40px;padding:2px;cursor:pointer;border:1px solid var(--border);border-radius:var(--r);">
      <input type="color" value="#37474F" data-admin-status-fg="new" style="width:60px;height:40px;padding:2px;cursor:pointer;border:1px solid var(--border);border-radius:var(--r);">
      <button class="btn btn-primary btn-sm" onclick="saveStatusMetaRecord('new')">Add</button>
    </div>`;


  const reportRows=Object.values(REPORT_TEMPLATES).sort((a,b)=>a.order-b.order||a.label.localeCompare(b.label)).map(meta=>{
    const typeOptions=REPORT_TYPES.map(type=>`<option value="${type.id}"${type.id===meta.reportType?' selected':''}>${type.label}</option>`).join('');
    return `<div class="admin-row">
      <input value="${meta.id}" disabled style="padding:7px 9px;border:1px solid var(--border);border-radius:var(--r);background:#F7F9FC" placeholder="Report id">
      <input value="${meta.label}" data-admin-report-label="${meta.id}" style="padding:7px 9px;border:1px solid var(--border);border-radius:var(--r)" placeholder="Label">
      <select data-admin-report-type="${meta.id}" style="padding:7px 9px;border:1px solid var(--border);border-radius:var(--r)">${typeOptions}</select>
      <input value="${meta.order||999}" type="number" min="0" data-admin-report-order="${meta.id}" style="padding:7px 9px;border:1px solid var(--border);border-radius:var(--r)" placeholder="Order">
      <label style="font-size:12px;display:flex;align-items:center;gap:6px"><input type="checkbox" ${meta.visible?'checked':''} data-admin-report-visible="${meta.id}"> Visible</label>
      <div style="display:flex;gap:6px;justify-content:flex-end">
        <button class="btn btn-primary btn-sm" onclick="saveReportMetaRecord('${meta.id}')">Save</button>
        <button class="btn btn-danger btn-sm" onclick="deleteReportMetaRecord('${meta.id}')">Delete</button>
      </div>
      <div style="grid-column:1 / -1;font-size:11px;color:var(--text2);padding:4px 0;">${meta.description||''}</div>
    </div>`;
  }).join('');

  const newReportRow = `<div class="admin-row admin-row-add">
      <input value="" data-admin-report-id="new" class="admin-field" style="padding:7px 9px;border:1px solid var(--border);border-radius:var(--r);" placeholder="New report id">
      <input value="" data-admin-report-label="new" class="admin-field" style="padding:7px 9px;border:1px solid var(--border);border-radius:var(--r);" placeholder="Label">
      <select data-admin-report-type="new" class="admin-field" style="padding:7px 9px;border:1px solid var(--border);border-radius:var(--r);">${REPORT_TYPES.map(type=>`<option value="${type.id}">${type.label}</option>`).join('')}</select>
      <input value="999" type="number" min="0" data-admin-report-order="new" class="admin-field" style="padding:7px 9px;border:1px solid var(--border);border-radius:var(--r);" placeholder="Order">
      <label style="font-size:12px;display:flex;align-items:center;gap:6px"><input type="checkbox" checked data-admin-report-visible="new"> Visible</label>
      <button class="btn btn-primary btn-sm" onclick="saveReportMetaRecord('new')">Add</button>
    </div>`;

  const userRows=users.map(user=>{
    const userRole=user.role||'booking_officer';
    const roleOptions=getRoleSelectOptions(userRole);
    const permissionsValue=Array.isArray(user.permissions)?user.permissions.join(', '):String(user.permissions||'');
    const locked=user.username==='superadmin';
    const disabled=locked?'disabled':'';
    return `<div class="admin-row">
      <input value="${user.username}" disabled style="padding:7px 9px;border:1px solid var(--border);border-radius:var(--r);background:#F7F9FC" placeholder="Username">
      <input value="${user.name||''}" data-admin-user-name="${user.username}" style="padding:7px 9px;border:1px solid var(--border);border-radius:var(--r)" placeholder="Display name" ${disabled}>
      <input value="${user.depot||''}" data-admin-user-depot="${user.username}" style="padding:7px 9px;border:1px solid var(--border);border-radius:var(--r)" placeholder="Depot" ${disabled}>
      <select data-admin-user-role="${user.username}" style="padding:7px 9px;border:1px solid var(--border);border-radius:var(--r)" ${disabled}>${roleOptions}</select>
      <input value="${permissionsValue}" data-admin-user-permissions="${user.username}" style="padding:7px 9px;border:1px solid var(--border);border-radius:var(--r)" placeholder="Permissions comma-separated" ${disabled}>
      <input type="password" value="" data-admin-user-password="${user.username}" style="padding:7px 9px;border:1px solid var(--border);border-radius:var(--r)" placeholder="New password" autocomplete="new-password" ${disabled}>
      ${locked?'<span style="font-size:11px;color:var(--text2)">Full access (locked)</span>':`<button class="btn btn-primary btn-sm" onclick="saveUserAccount('${user.username}')">Save</button>`}
      <label style="grid-column:1 / -1;font-size:12px;display:flex;align-items:center;gap:6px"><input type="checkbox" ${user.isHQ?'checked':''} data-admin-user-hq="${user.username}" ${disabled}> HQ / global access</label>
    </div>`;
  }).join('');

  const newUserRow = `<div class="admin-row admin-row-add">
      <input value="" data-admin-user-username="newUser" class="admin-field" placeholder="New username">
      <input value="" data-admin-user-name="newUser" class="admin-field" placeholder="Display name">
      <input value="HQ" data-admin-user-depot="newUser" class="admin-field" placeholder="Depot">
      <select data-admin-user-role="newUser" class="admin-field">${getRoleSelectOptions('booking_officer')}</select>
      <input value="" data-admin-user-permissions="newUser" class="admin-field" placeholder="Permissions comma-separated">
      <input type="password" value="" data-admin-user-password="newUser" class="admin-field" placeholder="Password" autocomplete="new-password">
      <button class="btn btn-primary btn-sm" onclick="saveUserAccount('newUser')">Add user</button>
      <label style="grid-column:1 / -1;font-size:12px;display:flex;align-items:center;gap:6px"><input type="checkbox" data-admin-user-hq="newUser"> HQ / global access</label>
    </div>`;

  const adminSections={
    overview:`<div class="admin-card admin-overview-card">
      <div class="admin-section-header">
        <div>
          <div class="admin-section-title">Overview</div>
          <p class="admin-section-note">Pick a category from the dropdown or use a tile below. New items are added from the bottom row inside each section.</p>
        </div>
      </div>
      <div class="admin-overview-grid">
        <button class="admin-overview-tile" onclick="setAdminSectionView('depots')"><b>Depots</b><span>Labels, colors, rest hours</span></button>
        <button class="admin-overview-tile" onclick="setAdminSectionView('designations')"><b>Designations</b><span>Labels, aliases, rest eligibility</span></button>
        <button class="admin-overview-tile" onclick="setAdminSectionView('trainType')"><b>Train types</b><span>Reusable rolling stock labels</span></button>
        <button class="admin-overview-tile" onclick="setAdminSectionView('shift')"><b>Shifts</b><span>Shift codes and labels</span></button>
        <button class="admin-overview-tile" onclick="setAdminSectionView('status')"><b>Status</b><span>Badge text and colors</span></button>
        <button class="admin-overview-tile" onclick="setAdminSectionView('reports')"><b>Reports</b><span>Visibility, ordering, types</span></button>
        <button class="admin-overview-tile" onclick="setAdminSectionView('roles')"><b>Roles</b><span>Editable access groups</span></button>
        <button class="admin-overview-tile" onclick="setAdminSectionView('permissions')"><b>Permissions</b><span>Reusable access flags</span></button>
        <button class="admin-overview-tile" onclick="setAdminSectionView('users')"><b>Users</b><span>Accounts and access</span></button>
        <button class="admin-overview-tile" onclick="openAddModal()"><b>Crew members</b><span>Add single crew or bulk paste</span></button>
      </div>
    </div>`,
    depots:`<div class="admin-card">
      <div class="admin-section-header">
        <div>
          <div class="admin-section-title">Depots</div>
          <p class="admin-section-note">Edit depot IDs, labels, colors and rest hours here. Use the bottom row to add a new depot.</p>
        </div>
      </div>
      <div class="admin-row-header">
        <div>ID</div><div>Label</div><div>Color</div><div>Hours</div><div>Active</div><div></div>
      </div>
      ${depotRows}${newDepotRow}
    </div>`,
    designations:`<div class="admin-card">
      <div class="admin-section-header">
        <div>
          <div class="admin-section-title">Designations</div>
          <p class="admin-section-note">Use the predefined designation catalog and toggle the access flags for rest, login, crew, and user roles.</p>
        </div>
      </div>
      <div class="admin-row-header" style="grid-template-columns:minmax(140px,1fr) minmax(180px,1.2fr) 90px 90px 90px 90px 100px 90px;">
        <div>ID</div><div>Label</div><div>Rest</div><div>Login</div><div>Crew</div><div>User</div><div>Order</div><div></div>
      </div>
      ${designationRows}
    </div>`,
    status:`<div class="admin-card">
      <div class="admin-section-header">
        <div>
          <div class="admin-section-title">Crew statuses</div>
          <p class="admin-section-note">These system statuses are prefilled in the database and shown here read-only. SB is shown as Stand By in the crew entry form.</p>
        </div>
      </div>
      <div class="admin-row-header">
        <div>ID</div><div>Label</div><div>Background</div><div>Foreground</div><div></div><div></div>
      </div>
      ${statusRows}
    </div>`,
    reports:`<div class="admin-card">
      <div class="admin-section-header">
        <div>
          <div class="admin-section-title">Report templates</div>
          <p class="admin-section-note">Control which reports appear and how they are ordered.</p>
        </div>
      </div>
      <div class="admin-row-header">
        <div>ID</div><div>Label</div><div>Type</div><div>Order</div><div>Visible</div><div></div>
      </div>
      ${reportRows}
    </div>`,
    trainType:buildSimpleMetaSection('trainType', trainTypeMetadataCache),
    shift:buildSimpleMetaSection('shift', shiftMetadataCache),
    roles:buildAccessMetaSection('roles', roleMetadataCache),
    permissions:buildAccessMetaSection('permissions', permissionMetadataCache),
    users:`<div class="admin-card">
      <div class="admin-section-header">
        <div>
          <div class="admin-section-title">Users</div>
          <p class="admin-section-note">Manage usernames, roles, depot access and passwords here.</p>
        </div>
      </div>
      <div class="admin-row-header">
        <div>Username</div><div>Name</div><div>Depot</div><div>Role</div><div>Permissions</div><div>Password</div><div></div>
      </div>
      ${userRows}${newUserRow}
    </div>`,
  };

  const adminSectionOptions=[
    {id:'overview',label:'Overview'},
    {id:'depots',label:'Depots'},
    {id:'designations',label:'Designations'},
    {id:'status',label:'Status'},
    {id:'reports',label:'Reports'},
    {id:'roles',label:'Roles'},
    {id:'permissions',label:'Permissions'},
    {id:'trainType',label:'Train types'},
    {id:'shift',label:'Shifts'},
    {id:'users',label:'Users'},
  ];
  const activeAdminSection = adminSections[adminSectionView] ? adminSectionView : 'overview';

  if(pbody) {
    pbody.innerHTML=`
    <div class="admin-panel admin-shell">
      <div class="admin-page-header">
        <div>
          <div class="admin-page-kicker">Admin center</div>
          <div class="admin-page-title">Manage one category at a time</div>
          <div class="admin-page-note">Use the dropdown to switch between editable sections and keep the page focused. Global users can also add crew from here.</div>
        </div>
        <div class="admin-page-actions">
          <button class="btn btn-green btn-sm no-print" onclick="openAddModal()">+ Add crew</button>
          <select class="sel-sm admin-section-select" onchange="setAdminSectionView(this.value)">
            ${adminSectionOptions.map(section=>`<option value="${section.id}"${section.id===activeAdminSection?' selected':''}>${section.label}</option>`).join('')}
          </select>
          <button class="btn btn-ghost btn-sm no-print" onclick="reloadAdminData()">Reload</button>
        </div>
      </div>
      ${dbReady? '': '<div class="admin-warning">MySQL backend is unavailable.</div>'}
      <div class="admin-content">${adminSections[activeAdminSection]}</div>
      <div class="admin-card">
        <div class="admin-section-header">
          <div class="admin-section-title">Crew upload options</div>
        </div>
        <p>Use the Add Crew modal for quick single adds or bulk paste, then download the CSV template from the bulk tab when you need a repeatable import format.</p>
      </div>
    </div>`;
    }
  } catch(err) {
    console.error('Failed during admin UI build', err);
    if(pbody) pbody.innerHTML = errorPanel;
  }
}

async function deleteDepotMetaRecord(depotId){
  if(!confirm(`Delete ${depotId} from depots?`)) return;
  try{
    if(!db){
      await deleteLocalAdminRecord('depotMeta', depotId);
      await loadDepotMeta();
      renderAdmin();
      setSyncStatus('ok','Local depot removed');
      return;
    }
    const resp = await fetch(`/mysql/meta/${encodeURIComponent('depotMeta')}/${encodeURIComponent(depotId)}`, {
      method:'DELETE',
      headers:{'X-CSRF-TOKEN':getCsrfToken()},
    });
    if(!resp.ok){
      throw new Error(await resp.text());
    }
    await loadDepotMeta();
    renderAdmin();
    setSyncStatus('ok','Depot removed');
  }catch(err){
    console.error('Failed to delete depot', err);
    setSyncStatus('err','Delete failed');
    alert('Unable to remove the depot right now.');
  }
}

async function saveDepotMetaRecord(depotId){
  const idEl = document.querySelector(`[data-admin-depot-id="${depotId}"]`);
  const labelEl = document.querySelector(`[data-admin-depot-label="${depotId}"]`);
  const colorEl = document.querySelector(`[data-admin-depot-color="${depotId}"]`);
  const hoursEl = document.querySelector(`[data-admin-depot-hours="${depotId}"]`);
  const activeEl = document.querySelector(`[data-admin-depot-active="${depotId}"]`);
  const nextId = idEl?.value?.trim() || '';
  if(!nextId){
    alert('Depot id cannot be blank');
    return;
  }
  const meta = {
    id: nextId,
    label: (labelEl?.value||nextId).trim(),
    color: (colorEl?.value||'#37474F').trim(),
    restHours: Number(hoursEl?.value||12),
    order: getAllDepotMetadata().findIndex(item=>item.id===depotId) + 1 || getActiveDepots().indexOf(depotId) + 1,
    active: !!activeEl?.checked,
  };
  if(!db){
    try{
      await saveLocalAdminRecord('depotMeta', nextId, meta);
      await loadDepotMeta();
      renderAdmin();
      setSyncStatus('ok','Local metadata saved');
      return;
    }catch(err){
      console.error('Local save failed',err);
      setSyncStatus('err','Local save failed');
      alert('Unable to save depot metadata locally. Check the browser console.');
      return;
    }
  }
  await setDoc(doc(db,'depotMeta',nextId),meta,{merge:true});
  await loadDepotMeta();
  renderAdmin();
}

async function saveDesignationMetaRecord(designationId){
  const idEl = document.querySelector(`[data-admin-desig-id="${designationId}"]`);
  const labelEl = document.querySelector(`[data-admin-desig-label="${designationId}"]`);
  const nextId = designationId === 'new' ? (idEl?.value||'').trim() : designationId;
  if(!nextId){
    alert('Designation id cannot be blank');
    return;
  }
  const currentMeta = getAllDesignationMetadata().find(item=>item.id===designationId) || DEFAULT_DESIGNATION_DEFINITIONS.find(item=>item.id===designationId) || {id:nextId,label:nextId,aliases:[]};
  const orderEl=document.querySelector(`[data-admin-desig-order="${designationId}"]`);
  const restEl=document.querySelector(`[data-admin-desig-rest="${designationId}"]`);
  const roomEl=document.querySelector(`[data-admin-desig-room="${designationId}"]`);
  const loginEl=document.querySelector(`[data-admin-desig-login="${designationId}"]`);
  const crewEl=document.querySelector(`[data-admin-desig-crew="${designationId}"]`);
  const userEl=document.querySelector(`[data-admin-desig-user="${designationId}"]`);
  const meta={
    id: nextId,
    label: (labelEl?.value||currentMeta.label||nextId).trim(),
    aliases: Array.isArray(currentMeta.aliases) ? currentMeta.aliases : [],
    restEligible:!!restEl?.checked,
    runningRoomEligible:!!roomEl?.checked,
    canLogin:!!loginEl?.checked,
    isCrewMember:!!crewEl?.checked,
    isUser:!!userEl?.checked,
    order:Number(orderEl?.value||999),
  };
  if(!db){
    try{
      await saveLocalAdminRecord('designationMeta',nextId,meta);
      await loadDesignationMeta();
      renderAdmin();
      setSyncStatus('ok','Local metadata saved');
      return;
    }catch(err){
      console.error('Local save failed',err);
      setSyncStatus('err','Local save failed');
      alert('Unable to save designation metadata locally. Check the browser console.');
      return; 
    }
  }
  await setDoc(doc(db,'designationMeta',nextId),meta,{merge:true});
  await loadDesignationMeta();
  renderAdmin();
}

async function deleteDesignationMetaRecord(designationId){
  if(!confirm(`Delete ${designationId} from designations?`)) return;
  try{
    if(!db){
      await deleteLocalAdminRecord('designationMeta',designationId);
      await loadDesignationMeta();
      renderAdmin();
      setSyncStatus('ok','Local designation removed');
      return;
    }
    await deleteDoc(doc(db,'designationMeta',designationId));
    await loadDesignationMeta();
    renderAdmin();
    setSyncStatus('ok','Designation removed');
  }catch(err){
    console.error('Failed to delete designation', err);
    setSyncStatus('err','Delete failed');
    alert('Unable to remove the designation right now.');
  }
}

async function saveStatusMetaRecord(statusId){
  const idEl = document.querySelector(`[data-admin-status-id="${statusId}"]`);
  const nextId = statusId === 'new' ? (idEl?.value||'').trim() : statusId;
  if(!nextId){
    alert('Status id cannot be blank');
    return;
  }
  const labelEl=document.querySelector(`[data-admin-status-label="${statusId}"]`);
  const bgEl=document.querySelector(`[data-admin-status-bg="${statusId}"]`);
  const fgEl=document.querySelector(`[data-admin-status-fg="${statusId}"]`);
  const meta={
    id:nextId,
    label:(labelEl?.value||nextId).trim(),
    bg:(bgEl?.value||'#ECEFF1').trim(),
    fg:(fgEl?.value||'#37474F').trim(),
    order:STATUS_META[statusId]?.order ?? STATUSES.indexOf(statusId) ?? 999,
  };
  if(!db){
    try{
      await saveLocalAdminRecord('statusMeta',nextId,meta);
      await loadStatusMeta();
      renderAdmin();
      setSyncStatus('ok','Local metadata saved');
      return;
    }catch(err){
      console.error('Local save failed',err);
      setSyncStatus('err','Local save failed');
      alert('Unable to save status metadata locally. Check the browser console.');
      return;
    }
  }
  await setDoc(doc(db,'statusMeta',nextId),meta,{merge:true});
  await loadStatusMeta();
  renderAdmin();
}

async function deleteStatusMetaRecord(statusId){
  if(!confirm(`Delete ${statusId} from statuses?`)) return;
  try{
    if(!db){
      await deleteLocalAdminRecord('statusMeta',statusId);
      await loadStatusMeta();
      renderAdmin();
      setSyncStatus('ok','Local status removed');
      return;
    }
    await deleteDoc(doc(db,'statusMeta',statusId));
    await loadStatusMeta();
    renderAdmin();
    setSyncStatus('ok','Status removed');
  }catch(err){
    console.error('Failed to delete status', err);
    setSyncStatus('err','Delete failed');
    alert('Unable to remove the status right now.');
  }
}

async function saveReportMetaRecord(reportId){
  const idEl = document.querySelector(`[data-admin-report-id="${reportId}"]`);
  const nextId = reportId === 'new' ? (idEl?.value||'').trim() : reportId;
  if(!nextId){
    alert('Report id cannot be blank');
    return;
  }
  const labelEl=document.querySelector(`[data-admin-report-label="${reportId}"]`);
  const typeEl=document.querySelector(`[data-admin-report-type="${reportId}"]`);
  const orderEl=document.querySelector(`[data-admin-report-order="${reportId}"]`);
  const visibleEl=document.querySelector(`[data-admin-report-visible="${reportId}"]`);
  const meta={
    id:nextId,
    label:(labelEl?.value||nextId).trim(),
    reportType:(typeEl?.value||'status').trim(),
    order:Number(orderEl?.value||999),
    visible:!!visibleEl?.checked,
  };
  if(!db){
    try{
      await saveLocalAdminRecord('reportMeta',nextId,meta);
      await loadReportMeta();
      renderAdmin();
      setSyncStatus('ok','Local metadata saved');
      return;
    }catch(err){
      console.error('Local save failed',err);
      setSyncStatus('err','Local save failed');
      alert('Unable to save report metadata locally. Check the browser console.');
      return;
    }
  }
  await setDoc(doc(db,'reportMeta',nextId),meta,{merge:true});
  await loadReportMeta();
  renderAdmin();
}

async function deleteReportMetaRecord(reportId){
  if(!confirm(`Delete ${reportId} from reports?`)) return;
  try{
    if(!db){
      await deleteLocalAdminRecord('reportMeta', reportId);
      await loadReportMeta();
      renderAdmin();
      setSyncStatus('ok','Local report removed');
      return;
    }
    await deleteDoc(doc(db,'reportMeta',reportId));
    await loadReportMeta();
    renderAdmin();
    setSyncStatus('ok','Report removed');
  }catch(err){
    console.error('Failed to delete report', err);
    setSyncStatus('err','Delete failed');
    alert('Unable to remove the report right now.');
  }
}

async function saveUserAccount(username){
  const targetUsername = username==='newUser' ? (document.querySelector('[data-admin-user-username="newUser"]')?.value||'').trim() : username;
  if(!targetUsername){
    alert('Username cannot be blank');
    return;
  }
  const nameEl=document.querySelector(`[data-admin-user-name="${username}"]`);
  const depotEl=document.querySelector(`[data-admin-user-depot="${username}"]`);
  const roleEl=document.querySelector(`[data-admin-user-role="${username}"]`);
  const permissionsEl=document.querySelector(`[data-admin-user-permissions="${username}"]`);
  const passwordEl=document.querySelector(`[data-admin-user-password="${username}"]`);
  const hqEl=document.querySelector(`[data-admin-user-hq="${username}"]`);
  const role=roleEl?.value||'booking_officer';
  const permissions=String(permissionsEl?.value||'').split(',').map(item=>item.trim()).filter(Boolean);
  const isHQ=!!hqEl?.checked || role==='super_admin' || role==='hq_admin';
  const payload={
    username:targetUsername,
    name:(nameEl?.value||targetUsername).trim(),
    depot:(depotEl?.value||'HQ').trim(),
    role,
    permissions,
    isHQ,
    isSuperAdmin:role==='super_admin',
  };
  const password=(passwordEl?.value||'').trim();
  if(username==='newUser' && !password){
    alert('Password is required for a new user.');
    return;
  }
  if(password){
    payload.password=password;
  }
  if(!db){
    try{
      await saveLocalAdminRecord('users',targetUsername,payload);
      await loadAdminUsers();
      renderAdmin();
      setSyncStatus('ok','Local user saved');
      return;
    }catch(err){
      console.error('Local save failed',err);
      setSyncStatus('err','Local save failed');
      alert('Unable to save user account locally. Check the browser console.');
      return;
    }
  }
  await setDoc(doc(db,'users',targetUsername),payload,{merge:true});
  await renderAdmin();
}

/* ════════ MODALS ══════════════════════════════════════════════════════════ */
function onStatusChange(){
  const s=document.getElementById('mStatus').value;
  updateStatusPicker();
  updateStatusChangeSummary();
  if(currentModalInRoom){
    // Force the Resting status while checked into a running room.
    if(s!=='R'){
      document.getElementById('mStatus').value='R';
      updateStatusPicker();
      updateStatusChangeSummary();
    }
    applyInRoomStatusLock();
    document.getElementById('trainTypeRow').style.display='none';
    document.getElementById('restHoursRow').style.display='block';
    document.getElementById('restLocationRow').style.display='block';
    onRestLocationChange();
    return;
  }
  document.getElementById('trainTypeRow').style.display=s==='BK'?'block':'none';
  document.getElementById('restHoursRow').style.display=s==='R'?'block':'none';
  document.getElementById('restLocationRow').style.display=s==='R'?'block':'none';
  if(s==='R'){
    if(editKey && editKey.depot) setAwayDepotOptions(editKey.depot, document.getElementById('mAwayDepot')?.value||'');
    onRestLocationChange();
  } else {
    document.getElementById('restLocationHint').textContent='';
    document.getElementById('awayDepotRow').style.display='none';
  }
  updateStatusValidation();
}

function onRestLocationChange(){
  const loc=document.getElementById('mRestLocation').value;
  const awayRow=document.getElementById('awayDepotRow');
  awayRow.style.display=loc==='away'?'block':'none';
  updateRestLocationHint();
}

function updateRestLocationHint(){
  const s=document.getElementById('mStatus').value;
  const info=document.getElementById('restDepotInfo');
  const hint=document.getElementById('restLocationHint');
  if(s!=='R'){ if(info) info.textContent=''; if(hint) hint.textContent=''; return; }
  const homeDepot = editKey?.depot || 'Home';
  const homeHours = getRestHours({depot:homeDepot});
  if(info) info.textContent=`${homeDepot} depot - ${homeHours}h rest period when resting at home.`;
  const loc=document.getElementById('mRestLocation').value;
  if(loc==='away'){
    const away=document.getElementById('mAwayDepot')?.value||'selected away depot';
    const awayHours = getRestHours({depot:homeDepot,awayDepot:away});
    if(hint) hint.textContent=`Away depot rest is ${awayHours}h. Current away depot: ${away}.`;
  } else {
    if(hint) hint.textContent=`Home depot rest is ${homeHours}h.`;
  }
}

function setAwayDepotOptions(homeDepot,selectedAway=''){
  const awaySelect=document.getElementById('mAwayDepot');
  if(!awaySelect) return;
  awaySelect.innerHTML=getActiveDepots().filter(d=>d!==homeDepot).map(d=>`<option value="${d}"${d===selectedAway?' selected':''}>${d}</option>`).join('');
}

/* ════════ CREW DETAILS MODAL ════════════════════════════════════════════════ */
function closeCrewDetails(){
  if(crewDetailsTimer){clearInterval(crewDetailsTimer);crewDetailsTimer=null;}
  crewDetailsKey=null;
  const m=document.getElementById('crewModal');
  if(m)m.classList.remove('open');
}

function openCrewDetails(depot,id){
  const c=Object.values(state[depot]||{}).find(x=>x.id===id);
  if(!c)return;
  crewDetailsKey={depot,id};
  selectedMonthCrewKey=`${depot}::${id}`;
  const [abg,afc]=AVT_PAL[0];
  const segs=getDaySegments(c, CD);
  const finalStatus=getFinalStatusForDay(c,CD)||c.status||'';
  const isDriver=isDesignationRestEligible(c.grade);
  const sec=restSecondsLeft(c);
  const maxH=getRestHours(c);
  const restCd = finalStatus==='R'&&isDriver
    ? (sec===null
        ? '<div class="crew-cd" style="color:var(--text3)">No start time set</div>'
        : `<div class="crew-cd ${sec<=0?'done':cdClass(sec,maxH)}" id="crewDetailsTick">${sec<=0?'→ Standby':fmtCountdown(sec)}</div>`)
    : '';
  const inRoom=!!(c.rrRoomId && c.rrCheckedOut===false);
  let quickActions='';
  if(inRoom){
    quickActions=`<span class="qa-note">In running room (${c.rrRoomName||''}) — check out via the register first.</span>`;
  }else if(c.status==='SB'){
    quickActions=`<button class="qa qa-book" onclick="quickActionBook('${depot}','${id}')">Book</button><button class="qa qa-to" onclick="quickActionTripOff('${depot}','${id}')">Trip Off</button>`;
  }else if(c.status==='BK'){
    quickActions=`<button class="qa qa-bo" onclick="quickActionBookedOff('${depot}','${id}')">Booked Off</button>`;
  }else if(c.status==='R'){
    quickActions=`<button class="qa qa-sb" onclick="quickActionStandby('${depot}','${id}')">End Rest</button>`;
  }else if(['L','SK','T'].includes(c.status)){
    quickActions=`<button class="qa qa-sb" onclick="quickActionRecall('${depot}','${id}')">Recall to duty</button>`;
  }else if(['TO','ABS','NTB'].includes(c.status)){
    quickActions=`<button class="qa qa-sb" onclick="quickActionStandby('${depot}','${id}')">Set Stand By</button>`;
  }
  const days=Array.from({length:Math.max(1,CD)},(_,i)=>i+1);
  const summary=Object.keys(STATUS_META).map(code=>{
    const n=days.filter(d=>getFinalStatusForDay(c,d)===code).length;
    return [code,n];
  }).filter(([,n])=>n>0);
  const todaySegs=segs.length
    ? segs.map(seg=>{
        const sm=STATUS_META[seg.status_code]||{label:seg.status_code,bg:'#ECEFF1',fg:'#37474F'};
        return `<div class="cd-timeline-row">
          <div class="cd-timeline-rail"><span style="background:${sm.fg}"></span></div>
          <div class="cd-timeline-main">
            <div class="cd-timeline-time">${seg.start_time} – ${seg.end_time}</div>
            <div class="cd-timeline-status">${statusBadgeHtml(seg.status_code,'month-mini-badge')} ${sm.label}</div>
            <div class="cd-timeline-note">${seg.note||sm.label}${seg.status_code==='R'&&c.restStarted?` · rest started ${fmtTime(new Date(c.restStarted))}`:''}</div>
          </div>
        </div>`;
      }).join('')
    : '<div class="selected-day-none">No status recorded today.</div>';

  document.getElementById('crewModalTitle').textContent='Crew details';
  document.getElementById('crewModalSub').textContent=`${c.name} · ${c.id} · ${depot}`;
  document.getElementById('crewModalBody').innerHTML=`
    <div class="crew-detail-head">
      <div class="avt" style="background:${abg};color:${afc}">${initials(c.name)}</div>
      <div>
        <div class="crew-detail-name">${c.name}</div>
        <div class="crew-detail-sub">${getDesignationLabel(c.grade)} · ${c.depot} · Staff No. ${c.staff_number||'-'}</div>
      </div>
    </div>
    <div class="crew-detail-chips">
      <div class="crew-chip"><span>Status</span><strong>${statusBadgeHtml(finalStatus,'month-mini-badge')}</strong></div>
      <div class="crew-chip"><span>Route</span><strong>${c.route||'-'}</strong></div>
      <div class="crew-chip"><span>Shift</span><strong>${getCrewShiftLabel(c)}</strong></div>
      ${c.status==='BK'&&c.trainType?`<div class="crew-chip"><span>Train</span><strong>${getTrainTypeLabel(c.trainType)}</strong></div>`:''}
      ${c.rrRoomName?`<div class="crew-chip"><span>Room</span><strong>${c.rrRoomName}${c.rrRoomDepot&&c.rrRoomDepot!==c.depot?` (${c.rrRoomDepot})`:''} · ${c.rrCheckedOut?'Checked out':'Checked in'}</strong></div>`:''}
      <div class="crew-chip"><span>Since</span><strong>${c.since||'-'}</strong></div>
      <div class="crew-chip"><span>Notes</span><strong>${c.notes||'-'}</strong></div>
    </div>
    <div class="crew-detail-actions">${quickActions}</div>
    <div class="crew-detail-section">
      <div class="crew-detail-title">Today's timeline <span class="cd-dow">${DAY_NAMES[new Date(CY,CM,CD).getDay()]} ${CD} ${MONTH_NAME.split(' ')[0]}</span>${restCd}</div>
      <div class="cd-timeline">${todaySegs}</div>
    </div>
    <div class="crew-detail-section">
      <div class="crew-detail-title">Month summary <span class="cd-dow">${MONTH_NAME}</span></div>
      <div class="status-side-list">${summary.length?summary.map(([code,n])=>`<div class="status-side-item"><span class="status-side-chip status-${code}">${code}</span><span>${n} day${n===1?'':'s'}</span></div>`).join(''):'<div class="selected-day-none">No monthly records yet.</div>'}</div>
    </div>`;
  const m=document.getElementById('crewModal');
  if(m)m.classList.add('open');
  if(crewDetailsTimer)clearInterval(crewDetailsTimer);
  if(finalStatus==='R'&&isDriver){
    crewDetailsTimer=setInterval(()=>{
      const cur=Object.values(state[depot]||{}).find(x=>x.id===id);
      const el=document.getElementById('crewDetailsTick');
      if(!cur||!el||!document.getElementById('crewModal')?.classList.contains('open')){closeCrewDetails();return;}
      const s=restSecondsLeft(cur);
      if(s===null){el.textContent='No start time set';el.style.color='var(--text3)';return;}
      if(s<=0){el.textContent='→ Standby';el.className='crew-cd done';clearInterval(crewDetailsTimer);crewDetailsTimer=null;return;}
      el.textContent=fmtCountdown(s);
      el.className=`crew-cd ${cdClass(s,maxH)}`;
    },1000);
  }
}

function changeStatusFromDetails(){
  const depot=crewDetailsKey?.depot,id=crewDetailsKey?.id;
  closeCrewDetails();
  if(depot&&id)openUpdate(depot,id);
}

function openUpdate(depot,id){
  editKey={depot,id,day:null};
  const c=Object.values(state[depot]||{}).find(x=>x.id===id);if(!c)return;
  currentModalGrade=c.grade;
  currentModalInRoom=!!(c.rrRoomId && c.rrCheckedOut===false && c.status==='R');
  setDaySegmentEditorVisible(false);
  populateStatusSelects(c.status||'SB');
  populateTrainTypeSelect(c.trainType||'');
  document.getElementById('mTitle').textContent='Update crew status';
  document.getElementById('mSub').textContent=`${c.name} · ${c.id} · ${depot}`;
  document.getElementById('mStatus').value=c.status||'SB';
  document.getElementById('mTrainType').value=c.trainType||'';
  document.getElementById('mBookTime').value=c.bookTime||'';
  document.getElementById('mRoute').value=c.route||'';
  const staffNumberEl=document.getElementById('mStaffNumber');
  if(staffNumberEl) staffNumberEl.value=c.staff_number||'';
  populateShiftSelects(c.shift||'');
  document.getElementById('mNotes').value=c.notes||'';
  if(c.restStarted){
    const restDate = c.restStarted && c.restStarted.toDate ? c.restStarted.toDate() : new Date(c.restStarted);
    document.getElementById('mRestStart').value=isNaN(restDate.getTime())?new Date().toTimeString().substring(0,5):restDate.toTimeString().substring(0,5);
  } else document.getElementById('mRestStart').value=new Date().toTimeString().substring(0,5);
  document.getElementById('mRestLocation').value=c.awayDepot && c.awayDepot!==depot?'away':'home';
  setAwayDepotOptions(depot,c.awayDepot);
  onStatusChange();
  document.getElementById('mRemoveBtn').style.display='inline-flex';
  document.getElementById('modal').classList.add('open');
}

function openDayEdit(depot,id,day){
  editKey={depot,id,day};
  const c=Object.values(state[depot]||{}).find(x=>x.id===id);if(!c)return;
  selectedMonthCrewKey = `${depot}::${id}`;
  selectedMonthDay = day;
  currentModalGrade=c.grade;
  currentModalInRoom=(day===CD) && !!(c.rrRoomId && c.rrCheckedOut===false && c.status==='R');
  const daySegments=getDaySegments(c,day);
  const finalStatus=getFinalStatusForDay(c,day)||'SB';
  populateStatusSelects(finalStatus);
  populateTrainTypeSelect('');
  const dt=new Date(CY,CM,day);
  document.getElementById('mTitle').textContent='Edit daily position';
  document.getElementById('mSub').textContent=`${c.name} · ${DAY_NAMES[dt.getDay()]} ${day} ${MONTH_NAME.split(' ')[0]}`;
  document.getElementById('mStatus').value=finalStatus;
  document.getElementById('mTrainType').value='';document.getElementById('mBookTime').value='';
  document.getElementById('mRoute').value=c.route||'';
  const staffNumberEl=document.getElementById('mStaffNumber');
  if(staffNumberEl) staffNumberEl.value=c.staff_number||'';
  populateShiftSelects(c.shift||'');document.getElementById('mNotes').value='';
  document.getElementById('mRestStart').value=new Date().toTimeString().substring(0,5);
  document.getElementById('mRestLocation').value='home';
  setAwayDepotOptions(depot,'');
  onStatusChange();
  loadDaySegmentEditor(daySegments);
  document.getElementById('mRemoveBtn').style.display='none';
  updateStatusValidation();
  document.getElementById('modal').classList.add('open');
}

function closeModal(){document.getElementById('modal').classList.remove('open');setDaySegmentEditorVisible(false);editKey=null;currentModalGrade=null;currentModalInRoom=false;}

function countTripOffDays(c){
  const monthly=c.monthly||{};
  return Object.keys(monthly).filter(k=>k.startsWith('d')&&monthly[k]==='TO').length;
}

function confirmTripOffDay(c,day){
  const alreadyTo=!!(c.monthly&&c.monthly[`d${day}`]==='TO');
  if(alreadyTo) return true;
  const toDays=countTripOffDays(c);
  if(toDays>=4){
    return confirm(`${c.name} already has ${toDays} Trip Off day(s) this month (max 4). Give another Trip Off day anyway?`);
  }
  return true;
}

async function saveModal(){
  if(!editKey)return;
  setSyncStatus('spin','Saving…');
  try{
    const newStatus=document.getElementById('mStatus').value;
    const trainType=newStatus==='BK'?document.getElementById('mTrainType').value:'';
    const bookTime=newStatus==='BK'?document.getElementById('mBookTime').value:'';
    const restStartInput=document.getElementById('mRestStart').value;
    if(currentModalInRoom && newStatus!=='R'){
      alert('This crew member is checked into a running room and must remain on Resting. Check them out of the room before changing status.');
      return;
    }
    if(newStatus==='R' && !isRestAllowedForGrade(currentModalGrade)){
      alert('This designation does not qualify for Resting. Change the status before saving.');
      return;
    }

    if(editKey.day){
      const c=Object.values(state[editKey.depot]||{}).find(x=>x.id===editKey.id);if(!c){closeModal();return;}
      const daySegments=readDaySegmentEditor(editKey.day);
      if(!daySegments.length){alert('Add at least one status segment for this day.');return;}
      const finalSegment=daySegments[daySegments.length-1];
      let finalStatus=finalSegment.status_code;
      if(currentModalInRoom && finalStatus!=='R'){
        alert('This crew member is checked into a running room and must remain on Resting. Check them out of the room before changing status.');
        return;
      }
      if(finalStatus==='TO' && !confirmTripOffDay(c,editKey.day)) return;
      if(daySegments.some(seg=>seg.status_code==='R') && !isRestAllowedForGrade(currentModalGrade)){
        alert('This designation does not qualify for Resting. Change the status before saving.');
        return;
      }
      for(const seg of daySegments){
        if(timeToMinutes(seg.end_time) <= timeToMinutes(seg.start_time) && seg.end_time!=='23:59'){
          alert('Each status segment must end after it starts.');
          return;
        }
      }
      const finalTrainType=finalStatus==='BK'?document.getElementById('mTrainType').value:'';
      const finalBookTime=finalStatus==='BK'?document.getElementById('mBookTime').value:'';
      const monthly={...(c.monthly||{})};monthly[`d${editKey.day}`]=finalStatus;
      const status_segments = buildStatusSegmentsForDayList(c, editKey.day, daySegments);
      const restLocation=document.getElementById('mRestLocation').value;
      const awayDepot = finalStatus==='R' && restLocation==='away' ? document.getElementById('mAwayDepot')?.value||null : null;
      const upd={monthly,status_segments,awayDepot};
      if(editKey.day===CD){upd.status=finalStatus;upd.trainType=finalTrainType;upd.bookTime=finalBookTime;upd.since=fmtTime(new Date());upd.updatedBy=currentUser.username;if(finalStatus==='R'&&restStartInput){const[hh,mm]=restStartInput.split(':');const rs=new Date();rs.setHours(parseInt(hh),parseInt(mm),0,0);upd.restStarted=rs.toISOString();}else if(finalStatus!=='R')upd.restStarted=null;}
      await writeCrewDoc(editKey.depot,editKey.id,upd);
      setLog(`${c.name} Day ${editKey.day} saved with ${daySegments.length} segment${daySegments.length===1?'':'s'}.`);
    } else {
      const c=Object.values(state[editKey.depot]||{}).find(x=>x.id===editKey.id);if(!c){closeModal();return;}
      if(newStatus==='TO' && !confirmTripOffDay(c,CD)) return;
      const monthly={...(c.monthly||{})};monthly[`d${CD}`]=newStatus;
      let restStarted=c.restStarted||null;
      if(newStatus==='R'&&restStartInput){const[hh,mm]=restStartInput.split(':');const rs=new Date();rs.setHours(parseInt(hh),parseInt(mm),0,0);restStarted=rs.toISOString();}
      else if(newStatus!=='R')restStarted=null;
      const status_segments = buildStatusSegmentsForDay({...c,restStarted}, CD, newStatus);
      const restLocation=document.getElementById('mRestLocation').value;
      const awayDepot = newStatus==='R' && restLocation==='away' ? document.getElementById('mAwayDepot')?.value||null : null;
      const upd={status:newStatus,trainType,bookTime,route:document.getElementById('mRoute').value||c.route,staff_number:document.getElementById('mStaffNumber')?.value||c.staff_number||'',shift:document.getElementById('mShift').value,notes:document.getElementById('mNotes').value,since:fmtTime(new Date()),updatedBy:currentUser.username,restStarted,monthly,status_segments,awayDepot: newStatus==='R'?awayDepot:null};
      await writeCrewDoc(editKey.depot,editKey.id,upd);
      setLog(`${c.name}: ${STATUS_META[c.status]?.label} → ${STATUS_META[newStatus]?.label}${trainType?' ('+trainType+')':''}${bookTime?' @ '+bookTime:''}`);
    }
    setSyncStatus('ok','Saved');
  }catch(err){setSyncStatus('err','Save failed');setLog('Error: '+err.message);}
  closeModal();
  refreshPage();
}

/* ════════ QUICK STATUS ACTIONS ════════════════════════════════════════════ */
async function applyQuickStatus(depot,id,status,opts={}){
  const c=Object.values(state[depot]||{}).find(x=>x.id===id);
  if(!c)return;
  const now=new Date();
  const restStarted = status==='R' ? (opts.restStarted ?? now.toISOString()) : null;
  const status_segments=buildStatusSegmentsForDay({...c,restStarted}, CD, status, {note:opts.note||STATUS_META[status]?.label||status});
  const monthly={...(c.monthly||{})};monthly[`d${CD}`]=status;
  const actionNote=opts.notes?((c.notes?c.notes+'; ':'')+opts.notes):c.notes;
  const upd={
    status,
    trainType:status==='BK'?(opts.trainType||c.trainType||''):'',
    bookTime:status==='BK'?(opts.bookTime||c.bookTime||''):'',
    route:c.route||'',
    staff_number:c.staff_number||'',
    shift:c.shift||'',
    notes:actionNote,
    since:fmtTime(now),
    updatedBy:currentUser.username,
    restStarted,
    monthly,
    status_segments,
    awayDepot:status==='R'?(opts.awayDepot||null):null,
  };
  setSyncStatus('spin','Saving…');
  try{
    await writeCrewDoc(depot,id,upd);
    setSyncStatus('ok','Saved');
  }catch(err){
    setSyncStatus('err','Save failed');
    setLog('Error: '+err.message);
    throw err;
  }
}

function quickActionBook(depot,id){
  const c=Object.values(state[depot]||{}).find(x=>x.id===id);if(!c)return;
  closeCrewDetails();
  openUpdate(depot,id);
  const sel=document.getElementById('mStatus');
  if(sel){sel.value='BK';updateStatusPicker();onStatusChange();}
}

async function quickActionBookedOff(depot,id){
  const c=Object.values(state[depot]||{}).find(x=>x.id===id);if(!c)return;
  if(!isDesignationRestEligible(c.grade)){
    alert(`${c.name} (${getDesignationLabel(c.grade)}) does not qualify for Resting.`);
    return;
  }
  const dest=currentUser.depot||'HQ';
  try{
    await applyQuickStatus(depot,id,'R',{awayDepot:dest,note:`Booked off at ${dest}`,notes:`Booked off at ${dest}`});
    setLog(`${c.name}: Booked Off → Resting at ${dest}`);
  }catch(e){}
  closeCrewDetails();
  refreshPage();
}

async function quickActionRecall(depot,id){
  const c=Object.values(state[depot]||{}).find(x=>x.id===id);if(!c)return;
  try{
    await applyQuickStatus(depot,id,'SB',{note:'Recalled to duty',notes:'Recalled to duty'});
    setLog(`${c.name}: ${STATUS_META[c.status]?.label||c.status} → Stand By (recalled)`);
  }catch(e){}
  closeCrewDetails();
  refreshPage();
}

async function quickActionStandby(depot,id){
  const c=Object.values(state[depot]||{}).find(x=>x.id===id);if(!c)return;
  try{
    await applyQuickStatus(depot,id,'SB',{note:'Set to Stand By',notes:'Set to Stand By'});
    setLog(`${c.name}: → Stand By`);
  }catch(e){}
  closeCrewDetails();
  refreshPage();
}

async function quickActionTripOff(depot,id){
  const c=Object.values(state[depot]||{}).find(x=>x.id===id);if(!c)return;
  if(!confirmTripOffDay(c,CD)) return;
  try{
    await applyQuickStatus(depot,id,'TO',{note:'Trip Off day',notes:'Trip Off day'});
    setLog(`${c.name}: → Trip Off`);
  }catch(e){}
  closeCrewDetails();
  refreshPage();
}

/* ════════ REMOVE CREW ═════════════════════════════════════════════════════ */
async function confirmRemoveCrew(){
  if(!editKey)return;
  const depot=editKey.depot;
  const id=editKey.id;
  const c=Object.values(state[depot]||{}).find(x=>x.id===id);
  if(!c)return;
  if(!confirm(`Remove ${c.name} (${c.id}) from ${c.depot}?\n\nThis cannot be undone.`))return;
  closeModal();
  setSyncStatus('spin','Removing…');
  try{
    await removeCrewDoc(depot,id);
    if(state[depot]) delete state[depot][id];
    setLog(`${c.name} (${c.id}) removed from ${c.depot}.`);
    setSyncStatus('ok','Removed');
  }catch(err){setSyncStatus('err','Remove failed');setLog('Error: '+err.message);}
  refreshPage();
}

async function removeCrewDoc(depot,id){
  if(demoMode||!db){
    if(state[depot])delete state[depot][id];
    return;
  }
  await deleteDoc(doc(db,'crew',`${depot}_${id}`));
}

/* ════════ ADD CREW ════════════════════════════════════════════════════════ */
async function openAddModal(){
  const canChooseDepot = hasGlobalAccess() || currentUser?.depot === 'HQ';
  const defaultDepot = hqDepotView !== 'all' ? hqDepotView : (currentUser?.depot || getActiveDepots()[0] || 'HQ');
  document.getElementById('addModalSub').textContent='Depot: '+(canChooseDepot?'Select below':currentUser.depot);
  document.getElementById('addName').value='';document.getElementById('addRoute').value='';
  const addStaffNumberEl=document.getElementById('addStaffNumber');
  if(addStaffNumberEl) addStaffNumberEl.value='';
  document.getElementById('bulkText').value='';
  const depotSelect=document.getElementById('addDepot');
  if(depotSelect){
    depotSelect.parentElement.style.display=canChooseDepot?'block':'none';
    depotSelect.innerHTML=getActiveDepots().map(d=>`<option value="${d}"${d===defaultDepot?' selected':''}>${d}</option>`).join('');
  }
  // Ensure metadata is loaded so dropdowns show live DB values.
  try{
    if(!Object.keys(getDesignationRegistry()||{}).length) await loadDesignationMeta();
    if(!STATUSES || !STATUSES.length) await loadStatusMeta();
    if(!SHIFT_OPTIONS || !SHIFT_OPTIONS.length) await loadShiftMeta();
    if(!TRAIN_TYPES || !TRAIN_TYPES.length) await loadTrainTypeMeta();
    // Local depot meta may affect depot list display
    if(!DEPOTS || !DEPOTS.length) await loadDepotMeta();
  }catch(err){
    console.warn('Failed to load admin metadata for add modal',err&&err.message);
  }

  populateDesignationSelect();
  populateTrainTypeSelect();
  populateShiftSelects();
  switchAddTab('single');
  document.getElementById('addStatus').value='SB';
  document.getElementById('addModal').classList.add('open');
}
function closeAddModal(){document.getElementById('addModal').classList.remove('open');}
function switchAddTab(t){
  document.getElementById('addSingle').style.display=t==='single'?'block':'none';
  document.getElementById('addBulk').style.display=t==='bulk'?'block':'none';
  document.getElementById('tab-single').style.borderBottomColor=t==='single'?'var(--kr-red)':'transparent';
  document.getElementById('tab-single').style.color=t==='single'?'var(--kr-red)':'var(--text2)';
  document.getElementById('tab-bulk').style.borderBottomColor=t==='bulk'?'var(--kr-red)':'transparent';
  document.getElementById('tab-bulk').style.color=t==='bulk'?'var(--kr-red)':'var(--text2)';
  document.getElementById('addSaveBtn').textContent=t==='single'?'Add crew member':'Add all from list';
}

function parseCsvRow(line){
  const parts=[]; let current=''; let inQuotes=false;
  for(let i=0;i<line.length;i++){
    const ch=line[i];
    if(ch==='"'){
      if(inQuotes && line[i+1]==='"'){
        current+='"'; i++; continue;
      }
      inQuotes=!inQuotes;
      continue;
    }
    if(ch===',' && !inQuotes){
      parts.push(current.trim());
      current='';
      continue;
    }
    current+=ch;
  }
  if(current!==''||line.endsWith(',')) parts.push(current.trim());
  return parts;
}

function normalizeCsvHeader(value){
  return String(value||'').trim().toLowerCase().replace(/[^a-z0-9]+/g,'');
}

function downloadCrewUploadTemplate(){
  const depot = currentUser?.isHQ ? (hqDepotView !== 'all' ? hqDepotView : getActiveDepots()[0] || 'HQ') : currentUser?.depot || 'HQ';
  const csv = [
    'Name,Staff Number,Designation,Depot,Route,Status,Train Type,Shift,Notes',
    `"Ali Hassan","STAFF-001","locomotive_driver","${depot}","Mombasa-Nairobi","SB","Freight","Day shift","Ready for mass upload"`,
    `"Amina Njeri","STAFF-002","train_guard","${depot}","Nairobi-Kisumu","BK","Passenger","Day shift","Booked example row"`,
    `"Peter Ochieng","STAFF-003","shunter_driver","${depot}","Changamwe Yard","SB","Shunting","Day shift","Shift-work example row"`,
  ].join('\n');
  dlCSV(csv, 'KR_Crew_Upload_Template.csv');
}

function getBulkRowValue(row, headerMap, names, position, fallback=''){
  for(const name of names){
    const index = headerMap?.[name];
    if(index !== undefined && row[index] !== undefined && String(row[index]).trim() !== ''){
      return String(row[index]).trim();
    }
  }
  const raw = row[position];
  return raw !== undefined && String(raw).trim() !== '' ? String(raw).trim() : fallback;
}

async function saveAddCrew(){
  const canChooseDepot = hasGlobalAccess() || currentUser?.depot === 'HQ';
  const depot=canChooseDepot?(document.getElementById('addDepot')?.value||hqDepotView||getActiveDepots()[0]||'HQ'):currentUser.depot;
  if(depot==='all'||depot==='HQ'||!depot){alert('Please select a specific depot first.');return;}
  const isBulk=document.getElementById('addBulk').style.display!=='none';
  if(!isBulk){
    const grade=document.getElementById('addGrade').value;
    const initStatus=document.getElementById('addStatus').value;
    if(initStatus==='R' && !isRestAllowedForGrade(grade)){
      alert('This designation does not qualify for Resting. Please choose another status.');
      return;
    }
  }
  setSyncStatus('spin','Adding…');
  try{
    if(isBulk){
      const raw=document.getElementById('bulkText').value||'';
      const lines=raw.split(/\r?\n/).map(l=>l.trim()).filter(Boolean);
      if(!lines.length){alert('Paste one or more crew rows first.');return;}
      const firstRowParts = parseCsvRow(lines[0]);
      const firstRowHeaders = firstRowParts.map(normalizeCsvHeader);
      const headerNames = ['name','fullname','staffnumber','staffno','staffnum','staff_number','designation','grade','depot','route','status','traintype','shift','notes'];
      const hasHeaderRow = firstRowHeaders.some(header => headerNames.includes(header));
      const headerMap = hasHeaderRow ? firstRowHeaders.reduce((acc, header, index)=>{ if(header) acc[header] = index; return acc; }, {}) : null;
      if(hasHeaderRow) lines.shift();
      let added=0;
      for(const line of lines){
        const parts=parseCsvRow(line);
        const name=getBulkRowValue(parts, headerMap, ['name','fullname'], 0, '');
        if(!name) continue;
        const staffNumber=getBulkRowValue(parts, headerMap, ['staffnumber','staffno','staffnum','staff_number'], 1, '');
        const grade=getBulkRowValue(parts, headerMap, ['designation','grade'], 2, 'locomotive_driver');
        const rowDepot=getBulkRowValue(parts, headerMap, ['depot'], 3, depot) || depot;
        const route=getBulkRowValue(parts, headerMap, ['route','assignment'], 4, '');
        const status=getBulkRowValue(parts, headerMap, ['status'], 5, 'SB') || 'SB';
        if(status==='R' && !isRestAllowedForGrade(grade)){
          setLog(`${name} skipped: ${grade} does not qualify for Resting.`);
          continue;
        }
        const trainType=getBulkRowValue(parts, headerMap, ['traintype','traintype'], 6, '');
        const shift=getBulkRowValue(parts, headerMap, ['shift'], 7, '');
        const notes=getBulkRowValue(parts, headerMap, ['notes','note'], 8, '');
        await addSingleCrew(rowDepot,name,grade,route,status,trainType,shift,notes,staffNumber);
        added++;
      }
      if(!added){alert('No valid crew rows were found. Each row requires a name.');return;}
      setLog(`${added} crew member${added===1?'':'s'} added to ${depot}.`);
    } else {
      const name=document.getElementById('addName').value.trim();
      if(!name){alert('Please enter a name.');return;}
      await addSingleCrew(depot,name,document.getElementById('addGrade').value,document.getElementById('addRoute').value,document.getElementById('addStatus').value,'',document.getElementById('addShift')?.value||'',document.getElementById('addNotes')?.value||'',document.getElementById('addStaffNumber')?.value||'');
      setLog(`${name} added to ${depot}.`);
    }
    setSyncStatus('ok','Saved');
  }catch(err){
    setSyncStatus('err','Failed');
    setLog('Error: '+(err.message||err));
  }
  closeAddModal();
  if(!db)refreshPage();
}

async function addSingleCrew(depot,name,grade,route,initStatus,trainType='',shift='',notes='',staffNumber=''){
  const existing=Object.values(state[depot]||{}).map(c=>c.id);
  const prefix=depot.substring(0,2).toUpperCase();
  let num=1;while(existing.includes(`${prefix}-${String(num).padStart(3,'0')}`))num++;
  const id=`${prefix}-${String(num).padStart(3,'0')}`;
  const monthly={};const status_segments=[];for(let d=1;d<=DAYS_IN_MON;d++)monthly[`d${d}`]='';if(initStatus){monthly[`d${CD}`]=initStatus;status_segments.push({day:CD,sort_order:100,status_code:initStatus});}
  const obj={id,name,staff_number:String(staffNumber||'').trim(),grade:normalizeDesignation(grade),depot,route,shift:String(shift||'').trim(),status:initStatus,trainType,notes,since:fmtTime(new Date()),monthly,status_segments,restStarted:null,awayDepot:null,updatedBy:currentUser.username,monthKey:MONTH_KEY};
  await addCrewDoc(depot,obj);
}

async function syncCrewMetaCollections(){
  await seedDepotMetaIfEmpty();
  await loadDepotMeta();
  await seedDesignationMetaIfEmpty();
  await loadDesignationMeta();
}

/* ════════ FILTER ══════════════════════════════════════════════════════════ */
function setFilter(f){activeFilter=f;updatePills();renderRoster();}
function updatePills(){document.querySelectorAll('.pill').forEach(p=>{p.className='pill';});const el=document.getElementById('pill-'+activeFilter);if(el)el.className='pill p'+(activeFilter==='all'?'a':activeFilter);}
function filterSearch(){const q=(document.getElementById('crewSearch')?.value||'').toLowerCase();document.querySelectorAll('#crewTbody tr').forEach(tr=>{tr.style.display=tr.textContent.toLowerCase().includes(q)||!q?'':'none';});}

/* ════════ EXPORT ══════════════════════════════════════════════════════════ */
function exportCSV(){
  const d=currentUser.isHQ?getActiveDepots():[currentUser.depot];const all=getAllCrew(state, d);
  let csv='Staff Number,Name,Designation,Depot,Route,Shift,Status,Today,Train Type,Booked Time,Rest Remaining,Since,Notes\n';
  all.forEach(c=>{
    const sec=restSecondsLeft(c);const rem=sec!==null&&sec>0?fmtCountdown(sec):(sec===0?'Complete':'-');
    const daySegments = getDaySegments(c, CD);
    const todayStatus = daySegments.length > 1 ? daySegments.map(seg=>seg.status_code||seg.status||'').join(' → ') : (c.status || '');
    csv+=`"${getCrewStaffNumberLabel(c)}","${c.name}","${getDesignationLabel(c.grade)}","${c.depot}","${c.route||''}","${getCrewShiftLabel(c)}","${STATUS_META[c.status]?.label||c.status}","${todayStatus}","${c.trainType||''}","${c.status==='BK'&&c.bookTime?c.bookTime:''}","${rem}","${c.since||''}","${(c.notes||'').replace(/"/g,"'")}"\n`;
  });
  dlCSV(csv,`KR_Status_${todayStr()}.csv`);
}
function exportMonthlyCSVLegacy(){
  const d=currentUser.isHQ?getActiveDepots():[currentUser.depot];
  const all=getAllCrew(state, d).sort((a,b)=>a.name.localeCompare(b.name));
  let hdr='ID,Name,Designation,Depot';
  for(let i=1;i<=DAYS_IN_MON;i++) hdr+=`,${i}`;
  const statusCols = getStatusCodes();
  hdr += ',' + statusCols.join(',') + '\n';
  let csv = hdr;
  all.forEach(c=>{
    let row=`"${c.id}","${c.name}","${getDesignationLabel(c.grade)}","${c.depot}"`;
    const sm = Object.fromEntries(statusCols.map(s=>[s,0]));
    for(let i=1;i<=DAYS_IN_MON;i++){
      const code=getDaySegmentCode(c,i)||'';
      if(Object.prototype.hasOwnProperty.call(sm,code)) sm[code]++;
      row+=`,"${code}"`;
    }
    row += ',' + statusCols.map(s=>sm[s]||0).join(',');
    csv+=row+'\n';
  });
  dlCSV(csv,`KR_Monthly_${MONTH_NAME.replace(' ','_')}.csv`);
}
function exportAbsenceCSV(){
  const d=currentUser.isHQ?getActiveDepots():[currentUser.depot];
  const absenceCodes = getAbsenceStatusCodes();
  const all=getAllCrew(state, d).filter(c=>absenceCodes.includes(c.status));
  let csv='ID,Name,Designation,Depot,Status,Reason/Notes,Last Updated\n';
  all.forEach(c=>{
    csv+=`"${c.id}","${c.name}","${getDesignationLabel(c.grade)}","${c.depot}","${getStatusMeta(c.status).label}","${(c.notes||'').replace(/"/g,"'")}","${c.lastUpdated||''}"\n`;
  });
  dlCSV(csv,`KR_Absences_${todayStr()}.csv`);
}
window.doLogin = doLogin;
window.doLogout = doLogout;
window.goPage = goPage;
window.closeModal = closeModal;
window.confirmRemoveCrew = confirmRemoveCrew;
window.saveModal = saveModal;
window.switchAddTab = switchAddTab;
window.closeAddModal = closeAddModal;
window.openAddModal = openAddModal;
window.saveAddCrew = saveAddCrew;
window.openUpdate = openUpdate;
window.openDayEdit = openDayEdit;
window.openCrewDetails = openCrewDetails;
window.closeCrewDetails = closeCrewDetails;
window.changeStatusFromDetails = changeStatusFromDetails;
window.setStatusFromGroup = setStatusFromGroup;
window.selectMonthDay = selectMonthDay;
window.selectMonthlyCrewDay = selectMonthlyCrewDay;
window.addDaySegmentRow = addDaySegmentRow;
window.removeDaySegmentRow = removeDaySegmentRow;
window.syncPrimaryStatusFromSegments = syncPrimaryStatusFromSegments;
window.onStatusChange = onStatusChange;
window.exportCSV = exportCSV;
window.exportMonthlyCSV = exportMonthlyCSV;
window.exportUtilizationCSV = exportUtilizationCSV;
window.updateReportSummary = updateReportSummary;
window.exportAbsenceCSV = exportAbsenceCSV;
window.setFilter = setFilter;
window.filterSearch = filterSearch;
window.setHqDepotView = setHqDepotView;
window.setAdminSectionView = setAdminSectionView;
window.reloadAdminData = reloadAdminData;
window.renderAdmin = renderAdmin;
window.saveDepotMetaRecord = saveDepotMetaRecord;
window.saveDesignationMetaRecord = saveDesignationMetaRecord;
window.deleteDesignationMetaRecord = deleteDesignationMetaRecord;
window.saveStatusMetaRecord = saveStatusMetaRecord;
window.deleteStatusMetaRecord = deleteStatusMetaRecord;
window.saveReportMetaRecord = saveReportMetaRecord;
window.deleteReportMetaRecord = deleteReportMetaRecord;
window.saveSimpleMetaRecord = saveSimpleMetaRecord;
window.removeSimpleMetaRecord = removeSimpleMetaRecord;
window.saveAccessMetaRecord = saveAccessMetaRecord;
window.removeAccessMetaRecord = removeAccessMetaRecord;
window.runReport = runReport;
window.saveUserAccount = saveUserAccount;
window.downloadCrewUploadTemplate = downloadCrewUploadTemplate;
window.seedBackend = async () => {
  if(!db){setLog('Cannot bootstrap superadmin: backend not initialized.');return;}
  await loadBackendConfig();
  await loadAdminUsers();
  setLog('Superadmin bootstrap complete.');
};
/* ════════ UI ═══════════════════════════════════════════════════════════════ */

document.addEventListener('keydown',e=>{if(e.key==='Escape'){closeModal();closeAddModal();closeCrewDetails();}});
['lUser','lPass'].forEach(id=>{document.getElementById(id)?.addEventListener('keydown',e=>{if(e.key==='Enter')doLogin();});});

async function bootApp(){
  populateLoginDepotOptions();
  const cfg = await loadBackendConfig();
  if(cfg){
    const ok = initBackend(cfg);
    if(ok){
      await seedBackendUsers();
      const hasSession = await restoreSession();
      setSyncStatus('ok','MySQL connected');
      setLoginHint(false);
      setLog(hasSession?'MySQL connected - session restored.':'MySQL connected - ready.');
      if(document.getElementById('app')){
        document.getElementById('app').classList.add('show');
      }
        // mark app ready so lazy page modules can run
        window.__CREW_APP_READY = true;
        // restoreSession already renders the requested server page.
    } else {
      setSyncStatus('err','Backend initialization failed');
      setLoginHint(false);
      const errEl = document.getElementById('loginErr');
      if(errEl) errEl.textContent = 'Backend init failed. Check console for details.';
    }
  } else {
    setSyncStatus('err','Backend unavailable');
    setLoginHint(false);
    const errEl = document.getElementById('loginErr');
    if(errEl) errEl.textContent = 'Backend not reachable. Check the Laravel/MySQL connection and reload.';
  }
}

if (document.readyState === 'loading') {
  window.addEventListener('DOMContentLoaded', bootApp);
} else {
  bootApp();
}

