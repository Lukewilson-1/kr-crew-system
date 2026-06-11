import { initFirebase, loadFirebaseConfig, db, demoMode, setDemoMode } from './firebase.js';
import { getRestHours, restSecondsLeft, fmtCountdown, cdClass, getAllCrew, cts, initials, fmtTime, todayStr, fmtLastUpd, kpiHtml, dlCSV } from './helpers.js';
import { DEFAULT_DEPOTS, DEFAULT_DEPOT_META, DEFAULT_DESIGNATION_REGISTRY, getDesignationLabel, getDesignationOptions, isDesignationRestEligible, normalizeDesignation, setDesignationRegistry } from './constants.js';
import { collection, query, where, onSnapshot, getDocs, getDoc, doc, setDoc, deleteDoc, writeBatch, serverTimestamp } from "https://www.gstatic.com/firebasejs/10.12.0/firebase-firestore.js";
/* ════════ CONSTANTS ════════════════════════════════════════════════════════ */
let DEPOTS=[...DEFAULT_DEPOTS];
let DEPOT_COLORS=Object.fromEntries(Object.values(DEFAULT_DEPOT_META).map(meta=>[meta.id,meta.color]));
let REST_HOURS=Object.fromEntries(Object.values(DEFAULT_DEPOT_META).map(meta=>[meta.id,meta.restHours]));
const HOME_REST_HOURS=12;
const AWAY_REST_HOURS=10;
const DEFAULT_STATUS_META={
  BK:{label:'Booked',    bg:'#E8F5E9',fg:'#1B5E20'},
  SB:{label:'Standby',   bg:'#E3F2FD',fg:'#0D47A1'},
  R: {label:'Resting',   bg:'#F3E5F5',fg:'#4A148C'},
  L: {label:'On Leave',  bg:'#FFF3E0',fg:'#E65100'},
  SK:{label:'Sick',      bg:'#FFEBEE',fg:'#B71C1C'},
  T: {label:'Training',  bg:'#E0F2F1',fg:'#00695C'},
  NTB:{label:'NTB',      bg:'#ECEFF1',fg:'#37474F'},
  TO:{label:'Trip Off',  bg:'#FCE4EC',fg:'#AD1457'},
};
let STATUS_META={...DEFAULT_STATUS_META};
const TRAIN_TYPES=['Freight','Commuter','Passenger','Engineering','Shunting'];
<<<<<<< HEAD
=======
const DRIVER_GRADES=['Locomotive driver'];
>>>>>>> e4f6718b4073d665798519b29bb92e2b448f6451
const DEFAULT_STATUSES=['BK','SB','R','L','SK','T','NTB','TO'];
let STATUSES=[...DEFAULT_STATUSES];
const AVT_PAL=[['#E8F5E9','#1B5E20'],['#E3F2FD','#0D47A1'],['#FFF3E0','#E65100'],['#F3E5F5','#4A148C'],['#FFEBEE','#B71C1C'],['#E0F2F1','#00695C'],['#FFFDE7','#F57F17']];
const USER_ROLE_OPTIONS=[
  {id:'super_admin',label:'Super Admin'},
  {id:'hq_admin',label:'HQ Admin'},
  {id:'station_officer',label:'Station Officer'},
  {id:'booking_officer',label:'Booking Officer'},
  {id:'crew_admin',label:'Crew Admin'},
];
const ACCOUNTS={
  super_admin:{pw:'super1234',depot:'HQ',name:'Super Administrator',role:'super_admin'},
  hq_admin:{pw:'hq1234',depot:'HQ',name:'HQ Administrator',role:'hq_admin'},
  rsf_officer:{pw:'rsf123',depot:'Makadara',name:'RSF Station Officer',role:'station_officer'},
  changamwe_officer:{pw:'cga123',depot:'Changamwe',name:'CGA Booking Officer',role:'booking_officer'},
  mtito_officer:{pw:'mtt123',depot:'Mtito',name:'MTT Booking Officer',role:'booking_officer'},
  makadara_officer:{pw:'mkd123',depot:'Makadara',name:'MKD Booking Officer',role:'booking_officer'},
  nakuru_officer:{pw:'nkr123',depot:'Nakuru',name:'NKR Booking Officer',role:'booking_officer'},
  kisumu_officer:{pw:'ksm123',depot:'Kisumu',name:'KSM Booking Officer',role:'booking_officer'},
  eldoret_officer:{pw:'eld123',depot:'Eldoret',name:'ELD Booking Officer',role:'booking_officer'},
};

async function seedUsersIfEmpty(){
  if(!db) return;
  const snap = await getDocs(collection(db,'users'));
  if(!snap.empty) return;
  const batch = writeBatch(db);
  Object.entries(ACCOUNTS).forEach(([username,acct])=>{
    batch.set(doc(db,'users',username),{
      username,
      pw:acct.pw,
      depot:acct.depot,
      name:acct.name,
      role:acct.role|| (acct.depot==='HQ' ? 'hq_admin' : 'booking_officer'),
      isHQ: acct.role==='super_admin' || acct.role==='hq_admin' || acct.depot==='HQ'
    });
  });
  await batch.commit();
}

async function ensureCoreAccessUsers(){
  if(!db) return;
  const snap = await getDocs(collection(db,'users'));
  const batch = writeBatch(db);
  const seen = new Set();
  snap.forEach(docSnap=>{
    const data=docSnap.data();
    seen.add(docSnap.id);
    const role=data.role || (data.isHQ ? (docSnap.id==='super_admin' ? 'super_admin' : 'hq_admin') : 'booking_officer');
    const isHQ=data.isHQ || role==='super_admin' || role==='hq_admin' || data.depot==='HQ';
    if(data.role!==role || data.isHQ!==isHQ){
      batch.set(doc(db,'users',docSnap.id),{role,isHQ},{merge:true});
    }
  });
  Object.entries(ACCOUNTS).forEach(([username,acct])=>{
    if(seen.has(username)) return;
    batch.set(doc(db,'users',username),{
      username,
      pw:acct.pw,
      depot:acct.depot,
      name:acct.name,
      role:acct.role|| (acct.depot==='HQ' ? 'hq_admin' : 'booking_officer'),
      isHQ: acct.role==='super_admin' || acct.role==='hq_admin' || acct.depot==='HQ'
    });
  });
  await batch.commit();
}
const SEED_CREW={
  Changamwe:[{id:'CG-001',name:'James Kamau Njoroge',grade:'Locomotive driver',route:'CGA–MTT'},{id:'CG-002',name:'Mary Wanjiku Mwangi',grade:'Locomotive driver',route:'CGA–NBI'},{id:'CG-003',name:'Peter Ochieng Otieno',grade:'Shunter',route:'CGA Yard'},{id:'CG-004',name:'Grace Akinyi Odhiambo',grade:'Guard',route:'CGA–MTT'},{id:'CG-005',name:'Brian Otieno Ouma',grade:'Technician',route:'Workshop'},{id:'CG-006',name:'Christine Atieno Auma',grade:'Station Master',route:'CGA Station'}],
  Mtito:[{id:'MT-001',name:'Samuel Mwangi Gitau',grade:'Locomotive driver',route:'MTT–NBI'},{id:'MT-002',name:'Alice Chebet Koech',grade:'Guard',route:'MTT–CGA'},{id:'MT-003',name:'Daniel Kipchoge Ruto',grade:'Locomotive driver',route:'MTT–NKR'},{id:'MT-004',name:'Lydia Wambui Kariuki',grade:'Station Master',route:'MTT Station'},{id:'MT-005',name:'Fredrick Mutua Kioko',grade:'Shunter',route:'MTT Yard'}],
  Makadara:[{id:'MK-001',name:'Esther Muthoni Kariuki',grade:'Station Master',route:'MKD Station'},{id:'MK-002',name:'John Mwangi Njiru',grade:'Locomotive driver',route:'MKD–CGA'},{id:'MK-003',name:'Fatuma Hassan Abdi',grade:'Guard',route:'MKD–MTT'},{id:'MK-004',name:'Kevin Otieno Omondi',grade:'Shunter',route:'MKD Yard'},{id:'MK-005',name:'Rose Njeri Kamau',grade:'Locomotive driver',route:'MKD–NBI'},{id:'MK-006',name:'Charles Kimani Mwangi',grade:'Technician',route:'Workshop'}],
  Nakuru:[{id:'NK-001',name:'Paul Kimani Waweru',grade:'Locomotive driver',route:'NKR–NBI'},{id:'NK-002',name:'Jane Muthoni Kariuki',grade:'Guard',route:'NKR–ELD'},{id:'NK-003',name:'Moses Otieno Owino',grade:'Locomotive driver',route:'NKR–KSM'},{id:'NK-004',name:"Catherine Wanjiru Ng'ang'a",grade:'Station Master',route:'NKR Station'},{id:'NK-005',name:'Elijah Koech Kipkirui',grade:'Shunter',route:'NKR Yard'}],
  Kisumu:[{id:'KS-001',name:'George Ouma Oketch',grade:'Locomotive driver',route:'KSM–NKR'},{id:'KS-002',name:'Agnes Achieng Otieno',grade:'Guard',route:'KSM–ELD'},{id:'KS-003',name:'David Odhiambo Onyango',grade:'Locomotive driver',route:'KSM–NBI'},{id:'KS-004',name:'Mercy Adhiambo Ochieng',grade:'Station Master',route:'KSM Station'},{id:'KS-005',name:'Isaac Ogutu Were',grade:'Technician',route:'Workshop'},{id:'KS-006',name:'Beatrice Awuor Oloo',grade:'Shunter',route:'KSM Yard'}],
  Eldoret:[{id:'EL-001',name:'Joseph Kipkoech Ngetich',grade:'Locomotive driver',route:'ELD–NKR'},{id:'EL-002',name:'Winnie Jepkosgei Kogo',grade:'Guard',route:'ELD–KSM'},{id:'EL-003',name:'Robert Kibet Chirchir',grade:'Locomotive driver',route:'ELD–NBI'},{id:'EL-004',name:'Esther Chelangat Bett',grade:'Station Master',route:'ELD Station'},{id:'EL-005',name:'Leonard Kipyego Biwott',grade:'Shunter',route:'ELD Yard'}],
};

/* ════════ DATE ════════════════════════════════════════════════════════════ */
const TODAY=new Date();
const CY=TODAY.getFullYear(),CM=TODAY.getMonth(),CD=TODAY.getDate();
const DAYS_IN_MON=new Date(CY,CM+1,0).getDate();
const MONTH_NAME=TODAY.toLocaleString('en-KE',{month:'long',year:'numeric'});
const DAY_NAMES=['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
const MONTH_KEY=`${CY}-${String(CM+1).padStart(2,'0')}`;

/* ════════ STATE ════════════════════════════════════════════════════════════ */
let currentUser=null;
let state={};        // {depot:{crewId:crewDoc}}
let listeners=[];
let currentPage='dashboard',activeFilter='all',hqDepotView='all',editKey=null;
let cdInterval=null; // countdown ticker
let currentModalGrade=null;
const SESSION_KEY='KR_Crew_Session';

function setHqDepotView(value){
  hqDepotView=value;
  if(currentPage==='roster') renderRoster();
  if(currentPage==='monthly') renderMonthly();
}

function getActiveDepots(){
  return DEPOTS.length ? DEPOTS : [...DEFAULT_DEPOTS];
}

function hasGlobalAccess(){
  return !!currentUser && (currentUser.isHQ || currentUser.isSuperAdmin);
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
  const restOption=statusEl.querySelector('option[value="R"]');
  if(!grade){
    if(restOption)restOption.disabled=false;
    if(hintEl)hintEl.style.display='none';
    if(saveBtn)saveBtn.disabled=false;
    return;
  }
  const allowed=isRestAllowedForGrade(grade);
  if(restOption)restOption.disabled=!allowed;
  if(statusEl.value==='R' && !allowed){
    if(hintEl){
<<<<<<< HEAD
      hintEl.textContent='Only locomotive driver designations may be placed in Resting. Please select Standby or another status.';
=======
      hintEl.textContent='Only Locomotive driver may be placed in Resting. Please select Standby or another status.';
>>>>>>> e4f6718b4073d665798519b29bb92e2b448f6451
      hintEl.style.display='block';
    }
    if(saveBtn)saveBtn.disabled=true;
  } else {
    if(hintEl)hintEl.style.display='none';
    if(saveBtn)saveBtn.disabled=false;
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
  if(!raw) return false;
  try{
    const saved = JSON.parse(raw);
    if(!saved.username||!saved.depot||!saved.name) return false;
    currentUser={username:saved.username,depot:saved.depot,name:saved.name,isHQ:!!saved.isHQ,isSuperAdmin:!!saved.isSuperAdmin,role:saved.role||''};
    hqDepotView=(saved.isHQ||saved.isSuperAdmin)?saved.hqDepotView||'all':'all';
    currentPage=saved.currentPage||'dashboard';
    document.getElementById('loginPage').classList.remove('show');
    document.getElementById('app').classList.add('show');
    document.getElementById('tbBadge').textContent=getTopAccessLabel();
    document.getElementById('tbUser').textContent=currentUser.name;
    if(currentUser.isHQ){
      document.getElementById('depotSection').style.display='block';
      document.getElementById('sbDepots').innerHTML=getActiveDepots().map(d=>`<div class="sb-depot" onclick="setHqDepotView('${d}');goPage('roster')" id="sbd-${d}"><div class="sb-depot-dot" style="background:${DEPOT_COLORS[d]}"></div>${d}</div>`).join('');
    }
    const lds = hasGlobalAccess()?getActiveDepots():[currentUser.depot];
    if(db){
      await seedDepotMetaIfEmpty();
      await loadDepotMeta();
      await seedDesignationMetaIfEmpty();
      await loadDesignationMeta();
      await seedUsersIfEmpty();
      await seedStatusMetaIfEmpty();
      await loadStatusMeta();
      populateDesignationSelect();
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
  }catch(err){console.error('Session restore failed',err);return false;}
}

/* ════════ REST COUNTDOWN ══════════════════════════════════════════════════ */

// Auto-promote resting drivers to Standby when countdown expires
async function checkRestExpirations(){
  for(const depot of getActiveDepots()){
    const crew=Object.values(state[depot]||{});
    for(const c of crew){
      if(c.status==='R'&&isDesignationRestEligible(c.grade)){
        const sec=restSecondsLeft(c);
        if(sec!==null&&sec<=0){
          const hours = getRestHours(c);
          const updates={status:'SB',since:fmtTime(new Date()),restStarted:null,updatedBy:'Auto-system',notes:`Auto-promoted from Resting after ${hours}h`};
          await writeCrewDoc(depot,c.id,updates);
          setLog(`${c.name} (${depot}) rest period complete - auto-promoted to Standby`);
        }
      }
    }
  }
}

function useDemoMode(){
  setDemoMode(true);
  state={};
  for(const depot of getActiveDepots()){
    state[depot]={};
    if(!SEED_CREW[depot]) continue;
    SEED_CREW[depot].forEach((c,i)=>{
      const monthly={};
      for(let d=1;d<=DAYS_IN_MON;d++){const dt=new Date(CY,CM,d);const we=dt.getDay()===0||dt.getDay()===6;const pool=we?['R','R','SB']:['BK','BK','BK','SB','L','SK','T','R','NTB'];monthly[`d${d}`]=pool[(i*7+d*3)%pool.length];}
      const todaySt=monthly[`d${CD}`]||'SB';
      state[depot][c.id]={...c,grade:normalizeDesignation(c.grade),depot,status:todaySt,trainType:todaySt==='BK'?TRAIN_TYPES[i%TRAIN_TYPES.length]:'',shift:'Day (06:00–14:00)',notes:'',since:'06:00',monthly,restStarted:todaySt==='R'?new Date(Date.now()-3*3600*1000).toISOString():null,awayDepot:null,updatedBy:'System',lastUpdated:new Date().toISOString()};
    });
  }
  document.getElementById('loginPage').classList.add('show');
  setSyncStatus('err','Offline demo mode');
  setLoginHint(true);
}

async function seedDepotIfEmpty(depot){
  if(!db)return;
  const snap=await getDocs(query(collection(db,'crew'), where('depot','==',depot)));
  if(!snap.empty)return;
  if(!SEED_CREW[depot]) return; // no default seed data for this depot
  const batch=writeBatch(db);
  SEED_CREW[depot].forEach((c,i)=>{
    const monthly={};
    for(let d=1;d<=DAYS_IN_MON;d++){const dt=new Date(CY,CM,d);const we=dt.getDay()===0||dt.getDay()===6;const pool=we?['R','R','SB']:['BK','BK','BK','SB','L','SK','T','R'];monthly[`d${d}`]=pool[(i*7+d*3)%pool.length];}
    const ts=monthly[`d${CD}`]||'SB';
    const restStarted = ts==='R' ? new Date().toISOString() : null;
    const ref=doc(db,'crew',`${depot}_${c.id}`);
    batch.set(ref,{...c,grade:normalizeDesignation(c.grade),depot,status:ts,trainType:ts==='BK'?TRAIN_TYPES[i%5]:'',shift:'Day (06:00–14:00)',notes:'',since:'06:00',monthly,restStarted,awayDepot:null,updatedBy:'System',lastUpdated:serverTimestamp(),monthKey:MONTH_KEY});
  });
  await batch.commit();
}

function normalizeDepotMetaRecord(docSnap){
  const data=docSnap.data();
  const id=data.id||docSnap.id;
  if(!id) return null;
  return {
    id,
    label:data.label||id,
    color:data.color||DEPOT_COLORS[id]||'#37474F',
    restHours:typeof data.restHours==='number'?data.restHours:(REST_HOURS[id]||12),
    order:typeof data.order==='number'?data.order:999,
    active:data.active!==false,
  };
}

async function seedDepotMetaIfEmpty(){
  if(!db) return;
  const snap = await getDocs(collection(db,'depotMeta'));
  if(!snap.empty) return;
  const batch = writeBatch(db);
  Object.values(DEFAULT_DEPOT_META).forEach(meta=>{
    batch.set(doc(db,'depotMeta',meta.id),meta);
  });
  await batch.commit();
}

async function loadDepotMeta(){
  if(!db) return;
  try{
    const snap = await getDocs(collection(db,'depotMeta'));
    if(snap.empty){
      DEPOTS=[...DEFAULT_DEPOTS];
      DEPOT_COLORS=Object.fromEntries(Object.values(DEFAULT_DEPOT_META).map(meta=>[meta.id,meta.color]));
      REST_HOURS=Object.fromEntries(Object.values(DEFAULT_DEPOT_META).map(meta=>[meta.id,meta.restHours]));
      return;
    }
    const records=[];
    const colors={};
    const hours={};
    snap.forEach(docSnap=>{
      const meta=normalizeDepotMetaRecord(docSnap);
      if(!meta) return;
      colors[meta.id]=meta.color;
      hours[meta.id]=meta.restHours;
      if(meta.active) records.push(meta);
    });
    records.sort((a,b)=>(a.order??999)-(b.order??999)||a.label.localeCompare(b.label));
    DEPOTS=records.map(meta=>meta.id);
    DEPOT_COLORS={...Object.fromEntries(Object.values(DEFAULT_DEPOT_META).map(meta=>[meta.id,meta.color])),...colors};
    REST_HOURS={...Object.fromEntries(Object.values(DEFAULT_DEPOT_META).map(meta=>[meta.id,meta.restHours])),...hours};
  }catch(err){
    console.error('Failed to load depot metadata',err);
    DEPOTS=[...DEFAULT_DEPOTS];
  }
}

async function seedStatusMetaIfEmpty(){
  if(!db) return;
  const snap = await getDocs(collection(db,'statusMeta'));
  if(!snap.empty) return;
  const batch = writeBatch(db);
  DEFAULT_STATUSES.forEach((id,index)=>{
    const meta = DEFAULT_STATUS_META[id];
    batch.set(doc(db,'statusMeta',id),{
      id,
      label:meta.label,
      bg:meta.bg,
      fg:meta.fg,
      order:index
    });
  });
  await batch.commit();
}

async function loadStatusMeta(){
  if(!db) return;
  try{
    const snap = await getDocs(query(collection(db,'statusMeta')));
    if(snap.empty) return;
    const meta={};
    const order=[];
    snap.forEach(docSnap=>{
      const data=docSnap.data();
      const id=data.id||docSnap.id;
      if(!id) return;
      meta[id]={label:data.label||id,bg:data.bg||'#ECEFF1',fg:data.fg||'#37474F'};
      order.push({id,order:typeof data.order==='number'?data.order:999});
    });
    if(order.length){
      order.sort((a,b)=>a.order-b.order);
      STATUSES=order.map(x=>x.id);
    }
    STATUS_META=Object.keys(meta).length?meta:STATUS_META;
  }catch(err){console.error('Failed to load status metadata',err);}
}

function normalizeDesignationMetaRecord(docSnap){
  const data=docSnap.data();
  const id=data.id||docSnap.id;
  if(!id) return null;
  return {
    id,
    label:data.label||id,
    aliases:Array.isArray(data.aliases)?data.aliases:data.aliases?String(data.aliases).split(',').map(v=>v.trim()).filter(Boolean):[],
    restEligible:data.restEligible!==false,
    order:typeof data.order==='number'?data.order:999,
  };
}

async function seedDesignationMetaIfEmpty(){
  if(!db) return;
  const snap = await getDocs(collection(db,'designationMeta'));
  if(!snap.empty) return;
  const batch = writeBatch(db);
  Object.values(DEFAULT_DESIGNATION_REGISTRY).forEach((meta,index)=>{
    batch.set(doc(db,'designationMeta',meta.id),{
      ...meta,
      order:typeof meta.order==='number'?meta.order:index,
    });
  });
  await batch.commit();
}

async function loadDesignationMeta(){
  if(!db) return;
  try{
    const snap = await getDocs(collection(db,'designationMeta'));
    if(snap.empty){
      setDesignationRegistry(DEFAULT_DESIGNATION_REGISTRY);
      return;
    }
    const registry={};
    snap.forEach(docSnap=>{
      const meta=normalizeDesignationMetaRecord(docSnap);
      if(meta) registry[meta.id]=meta;
    });
    setDesignationRegistry(Object.keys(registry).length?registry:DEFAULT_DESIGNATION_REGISTRY);
  }catch(err){
    console.error('Failed to load designation metadata',err);
    setDesignationRegistry(DEFAULT_DESIGNATION_REGISTRY);
  }
}

function buildStatusOptions(selected='SB'){
  return STATUSES.map(id=>{
    const meta=STATUS_META[id]||{label:id};
    return `<option value="${id}"${id===selected?' selected':''}>${id} - ${meta.label}</option>`;
  }).join('');
}

function populateStatusSelects(selected='SB'){
  const statusSelect=document.getElementById('mStatus');
  const addStatusSelect=document.getElementById('addStatus');
  if(statusSelect) statusSelect.innerHTML = buildStatusOptions(selected);
  if(addStatusSelect) addStatusSelect.innerHTML = buildStatusOptions('SB');
}

function populateDesignationSelect(selected='locomotive_driver'){
  const addGradeSelect=document.getElementById('addGrade');
  if(addGradeSelect) addGradeSelect.innerHTML = getDesignationOptions(selected);
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

async function seedFirestoreUsers(){
  if(!db) return;
  await seedDepotMetaIfEmpty();
  await loadDepotMeta();
  await seedDesignationMetaIfEmpty();
  await loadDesignationMeta();
  await migrateCrewDesignationKeys();
  await seedUsersIfEmpty();
  await seedStatusMetaIfEmpty();
  await loadStatusMeta();
  populateDesignationSelect();
  populateStatusSelects();
}

async function ensureRestCountdownSample(){
  if(!db) return;
  const samples=[
    {depot:'Changamwe',id:'CG-RST1',name:'Changamwe Rest 1',grade:'Locomotive driver',route:'CGA–MTT',restAgoHours:2},
    {depot:'Changamwe',id:'CG-RST2',name:'Changamwe Rest 2',grade:'Locomotive driver',route:'CGA–NBI',restAgoHours:8},
    {depot:'Eldoret',id:'EL-RST1',name:'Eldoret Rest 1',grade:'Locomotive driver',route:'ELD–NKR',restAgoHours:5},
    {depot:'Eldoret',id:'EL-RST2',name:'Eldoret Rest 2',grade:'Locomotive driver',route:'ELD–KSM',restAgoHours:9},
    {depot:'Eldoret',id:'EL-RST3',name:'Eldoret Rest 3',grade:'Locomotive driver',route:'ELD–NBI',restAgoHours:11}
  ];
  const batch=writeBatch(db);
  let added=0;
  for(const sample of samples){
    const ref=doc(db,'crew',`${sample.depot}_${sample.id}`);
    const snap=await getDoc(ref);
    if(snap.exists()) continue;
    const restStarted=new Date(Date.now()-sample.restAgoHours*3600*1000).toISOString();
    batch.set(ref,{
      id:sample.id,
      name:sample.name,
      grade:normalizeDesignation(sample.grade),
      route:sample.route,
      depot:sample.depot,
      shift:'Day (06:00-14:00)',
      status:'R',
      trainType:'',
      bookTime:'',
      notes:'Rest countdown sample',
      since:fmtTime(new Date()),
      monthly:{},
      restStarted,
      updatedBy:'System',
      monthKey:MONTH_KEY
    });
    added++;
  }
  if(added>0){
    await batch.commit();
    setLog(`Rest countdown sample crew added (${added}).`);
  }
}

function attachListeners(depots){
  listeners.forEach(u=>u());listeners=[];
  setSyncStatus('spin','Connecting…');
  depots.forEach(depot=>{
    if(!state[depot])state[depot]={};
    const unsub=onSnapshot(query(collection(db,'crew'), where('depot','==',depot)), snap=>{
      snap.docChanges().forEach(ch=>{const d=ch.doc.data();const id=d.id||ch.doc.id;if(ch.type==='removed')delete state[depot][id];else state[depot][id]=d;});
      setSyncStatus('ok','Live');
      if(currentPage!=='monthly')refreshPage();
    },err=>{setSyncStatus('err','Sync error');setLog('Error: '+err.message);});
    listeners.push(unsub);
  });
}

/* ════════ WRITE ═══════════════════════════════════════════════════════════ */
async function writeCrewDoc(depot,id,updates){
  normalizeCrewGradePayload(updates);
  if(demoMode||!db){if(!state[depot])state[depot]={};state[depot][id]={...state[depot][id],...updates};return;}
  await setDoc(doc(db,'crew',`${depot}_${id}`),{...updates,lastUpdated:serverTimestamp()},{merge:true});
}
async function addCrewDoc(depot,obj){
  normalizeCrewGradePayload(obj);
  if(demoMode||!db){if(!state[depot])state[depot]={};state[depot][obj.id]=obj;return;}
  await setDoc(doc(db,'crew',`${depot}_${obj.id}`),{...obj,lastUpdated:serverTimestamp()});
}

/* ════════ AUTH ════════════════════════════════════════════════════════════ */
async function doLogin(){
  const user=document.getElementById('lUser').value.trim().toLowerCase();
  const pass=document.getElementById('lPass').value;
  const depot=document.getElementById('lDepot').value;
  const err=document.getElementById('loginErr');err.style.display='none';
  if(!user||!pass){err.textContent='Enter username and password.';err.style.display='block';return;}
  let acct=null;
  if(db){
    try{
      const userRef = doc(db,'users',user);
      const userSnap = await getDoc(userRef);
      if(userSnap.exists()){
        const data = userSnap.data();
        if(data.pw !== pass){err.textContent='Incorrect password.';err.style.display='block';return;}
        acct = {depot:data.depot,name:data.name,isHQ:!!data.isHQ||data.role==='super_admin'||data.role==='hq_admin',isSuperAdmin:data.role==='super_admin',role:data.role||''};
      }
    }catch(err){console.error('User lookup failed',err);}
  }
  if(!acct){
    const local = ACCOUNTS[user];
    if(local){
      if(local.pw !== pass){err.textContent='Incorrect password.';err.style.display='block';return;}
      acct = {depot:local.depot,name:local.name,isHQ:local.role==='super_admin'||local.role==='hq_admin'||local.depot==='HQ',isSuperAdmin:local.role==='super_admin',role:local.role||''};
    }
  }
  if(!acct){
    err.textContent='Account not found. Use a Firestore user account or fall back to local credentials.';
    err.style.display='block';
    return;
  }
  currentUser={username:user,depot:acct.depot,name:acct.name,isHQ:acct.isHQ,isSuperAdmin:!!acct.isSuperAdmin,role:acct.role||''};
  document.getElementById('loginPage').classList.remove('show');
  document.getElementById('app').classList.add('show');
  document.getElementById('tbBadge').textContent=getTopAccessLabel();
  document.getElementById('tbUser').textContent=currentUser.name;
  if(currentUser.isHQ){
    document.getElementById('depotSection').style.display='block';
    document.getElementById('sbDepots').innerHTML=getActiveDepots().map(d=>`<div class="sb-depot" onclick="setHqDepotView('${d}');goPage('roster')" id="sbd-${d}"><div class="sb-depot-dot" style="background:${DEPOT_COLORS[d]}"></div>${d}</div>`).join('');
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
  goPage('dashboard');
  setInterval(updateClock,1000);updateClock();
  if(cdInterval)clearInterval(cdInterval);
  cdInterval=setInterval(()=>{checkRestExpirations();if(currentPage==='rest')renderRest();else updateCountdownsInTable();},10000);
  setLog(`Signed in - ${currentUser.name}`);
}

function doLogout(){
  listeners.forEach(u=>u());listeners=[];if(cdInterval)clearInterval(cdInterval);
  currentUser=null;hqDepotView='all';
  persistSession();
  document.getElementById('app').classList.remove('show');
  document.getElementById('loginPage').classList.add('show');
  document.getElementById('lUser').value='';document.getElementById('lPass').value='';
}

/* ════════ HELPERS ═════════════════════════════════════════════════════════ */
function setLog(m){const el=document.getElementById('logText');if(el)el.textContent=fmtTime(new Date())+' - '+m;}
function updateClock(){const el=document.getElementById('tbClock');if(el)el.textContent=fmtTime(new Date());}
function refreshPage(){const p={dashboard:renderDashboard,roster:renderRoster,rest:renderRest,monthly:renderMonthly,reports:renderReports,admin:renderAdmin};if(p[currentPage])p[currentPage]();}
function setSyncStatus(t,m){
  const dot=document.getElementById('syncDot');const lbl=document.getElementById('syncLabel');if(!dot)return;
  dot.className='sd sd-'+t;lbl.textContent=m;
  const sb=document.getElementById('syncBadge');sb.classList.remove('hide');if(t==='ok')setTimeout(()=>sb.classList.add('hide'),3000);
  const ld=document.getElementById('tbLiveDot');const lt=document.getElementById('tbLiveTxt');
  if(ld)ld.style.background=t==='ok'?'#69F0AE':t==='err'?'#EF5350':'#FFB300';
  if(lt)lt.textContent=t==='ok'?'Live':t==='err'?'Offline':'Syncing…';
}
function setLoginHint(demo){document.getElementById('loginHint').innerHTML=demo?'Demo mode - no sync. Use <code>hq_admin / hq1234</code>':'Credentials: <code>hq_admin / hq1234</code> | super admin: <code>super_admin / super1234</code> | depot officer accounts available';}

/* ════════ NAVIGATION ══════════════════════════════════════════════════════ */
function goPage(p){
  currentPage=p;if(p!=='roster')activeFilter='all';
  document.querySelectorAll('.sb-item').forEach(e=>e.classList.remove('active'));
  const el=document.getElementById('sb-'+p);if(el)el.classList.add('active');
  const titles={dashboard:'Dashboard',roster:'Crew Roster',rest:'Rest Countdowns',monthly:'Monthly View',reports:'Reports',admin:'Admin'};
  document.getElementById('phTitle').textContent=titles[p]||p;
  refreshPage();
  persistSession();
}

/* ════════ DASHBOARD ═══════════════════════════════════════════════════════ */
function renderDashboard(){
  const depots=currentUser.isHQ?getActiveDepots():[currentUser.depot];
  const all=getAllCrew(state, depots);const c=cts(all);
  document.getElementById('phSub').textContent=`Live booking board · ${MONTH_NAME}`;
  document.getElementById('phActions').innerHTML=demoMode?`<span style="font-size:11px;background:#FFF8E1;color:#F57F17;padding:3px 8px;border-radius:4px;border:1px solid #FFD54F">Offline demo</span>`:'';

  let html=kpiHtml([['TOT','Total crew',all.length],['BK','Booked',c.BK||0],['SB','Standby',c.SB||0],['R','Resting',c.R||0],['L','On Leave',c.L||0],['SK','Sick',c.SK||0],['T','Training',c.T||0],['NTB','NTB',c.NTB||0],['TO','Trip Off',c.TO||0]]);

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
      const col=DEPOT_COLORS[depot];const hasSick=(dc.SK||0)>0||(dc.NTB||0)>0;
      const lastUpd=crew.length?fmtLastUpd(crew.reduce((a,b)=>(a.lastUpdated||'')>(b.lastUpdated||'')?a:b).lastUpdated):'-';
      html+=`<div class="depot-card" onclick="setHqDepotView('${depot}');goPage('roster')">
        <div class="dc-hdr"><span class="dc-name" style="color:${col}">${depot}</span><div class="dc-alert-dot" style="background:${hasSick?'#E53935':'#43A047'}"></div></div>
        <div class="dc-bars">`;
      [['BK','Booked',DEPOT_COLORS.Changamwe],['SB','Standby','#1565C0'],['R','Resting','#6A1B9A'],['L','Leave','#E65100'],['SK','Sick','#B71C1C'],['T','Training','#00695C'],['NTB','NTB','#37474F']].forEach(([code,lbl,col2])=>{
        const n=dc[code]||0;const pct=Math.round((n/tot)*100);
        html+=`<div class="dc-bar-row"><span class="dc-bar-lbl">${lbl}</span><div class="dc-bar-track"><div class="dc-bar-fill" style="width:${pct}%;background:${col2}"></div></div><span class="dc-bar-val">${n}</span></div>`;
      });
      html+=`</div><div class="dc-foot"><span>${crew.length} crew</span><span class="dc-online"><div class="dc-online-dot"></div>Updated ${lastUpd}</span></div></div>`;
    });
    html+=`</div>`;
  } else {
    html+=`<div class="sec-hdr"><span class="sec-title">Today - ${currentUser.depot}</span><button class="btn btn-green btn-sm no-print" onclick="openAddModal()">+ Add crew</button></div>`;
    html+=crewTableHtml(currentUser.depot,false,true);
  }
  document.getElementById('pbody').innerHTML=html;
}

/* ════════ ROSTER ══════════════════════════════════════════════════════════ */
function renderRoster(){
  const depots=currentUser.isHQ?(hqDepotView==='all'?getActiveDepots():[hqDepotView]):[currentUser.depot];
  const label=currentUser.isHQ?(hqDepotView==='all'?'All Depots':hqDepotView+' Depot'):currentUser.depot+' Depot';
  document.getElementById('phSub').textContent=`${label} · ${MONTH_NAME}`;
  const allCrew=getAllCrew(state, depots);const c=cts(allCrew);
  document.getElementById('phActions').innerHTML=currentUser.isHQ?'':
    `<button class="btn btn-ghost btn-sm no-print" onclick="openAddModal()">+ Add crew</button>`;

  let html='';
  if(currentUser.isHQ){
    html+=`<div style="margin-bottom:11px;display:flex;gap:6px;align-items:center;flex-wrap:wrap">
      <button class="pill pa" onclick="setHqDepotView('all')">All</button>
      ${getActiveDepots().map(d=>`<button class="pill" onclick="setHqDepotView('${d}')" style="color:${DEPOT_COLORS[d]}">${d}</button>`).join('')}
    </div>`;
  }
  html+=kpiHtml([['TOT','Total',allCrew.length],['BK','Booked',c.BK||0],['SB','Standby',c.SB||0],['R','Resting',c.R||0],['L','Leave',c.L||0],['SK','Sick',c.SK||0],['T','Training',c.T||0],['NTB','NTB',c.NTB||0],['TO','Trip Off',c.TO||0]],'repeat(auto-fit,minmax(80px,1fr))');
  const showDepot=currentUser.isHQ&&hqDepotView==='all';
  html+=crewTableHtml(hqDepotView==='all'&&currentUser.isHQ?'all':depots[0],showDepot,!currentUser.isHQ);
  document.getElementById('pbody').innerHTML=html;
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
        <th>ID</th><th>Name / Designation</th>
        ${showDepotCol?'<th>Depot</th>':''}
        <th>Rest location</th>
        <th>Route</th><th>Shift</th><th>Status</th><th>Train Type</th><th>Depart</th>
        <th>Rest Countdown</th><th>Since</th><th>Notes</th>
        ${editable?'<th class="no-print"></th>':''}
      </tr></thead>
      <tbody id="crewTbody">`;

  if(allCrew.length===0){html+=`<tr><td colspan="12" class="no-rows">No crew match this filter.</td></tr>`;}
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
      <td style="font-size:11px;color:var(--text3);font-family:var(--mono)">${c.id}</td>
      <td><div class="nm"><div class="avt" style="background:${abg};color:${afc}">${initials(c.name)}</div><div><strong>${c.name}</strong><span>${getDesignationLabel(c.grade)}</span></div></div></td>
      ${showDepotCol?`<td><span style="color:${dc};font-weight:700">${c.depot}</span></td>`:''}
      <td style="font-size:11px;color:${c.awayDepot?'var(--kr-red)':'var(--text3)'}">${c.awayDepot?`${c.awayDepot} (away)`:'Home'}</td>
      <td style="font-size:11px">${c.route||'-'}</td>
      <td style="color:var(--text3);font-size:10px">${c.shift||'-'}</td>
      <td><span class="badge bd-${c.status}">${m.label}</span></td>
      <td>${c.status==='BK'&&c.trainType?`<span class="tt-${c.trainType}">${c.trainType}</span>`:'-'}</td>
      <td style="font-size:11px;font-family:var(--mono);color:${c.status==='BK'&&c.bookTime?'var(--booked)':'var(--text3)'};font-weight:${c.status==='BK'&&c.bookTime?'700':'400'}">${c.status==='BK'&&c.bookTime?c.bookTime:'-'}</td>
      <td>${cdHtml}</td>
      <td style="color:var(--text3);font-size:11px">${c.since||'-'}</td>
      <td style="font-size:11px;color:var(--text2);max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${c.notes||''}">${c.notes||'-'}</td>
      ${editable?`<td class="no-print" style="white-space:nowrap"><button class="tbl-act" onclick="openUpdate('${c.depot}','${c.id}')">✏ Edit</button></td>`:''}
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
  document.getElementById('phSub').textContent='Live rest countdowns - drivers only';
  document.getElementById('phActions').innerHTML='';
  const depots=currentUser.isHQ?getActiveDepots():[currentUser.depot];
  const all=getAllCrew(state, depots).filter(c=>isDesignationRestEligible(c.grade));
  const resting=all.filter(c=>c.status==='R');
  const standby=all.filter(c=>c.status==='SB');
  const booked=all.filter(c=>c.status==='BK');

  let html=kpiHtml([['TOT','Total Drivers',all.length],['R','Resting',resting.length],['SB','Standby',standby.length],['BK','Booked',booked.length]],'repeat(4,1fr)');
  html+=`<div class="sec-hdr"><span class="sec-title">Resting drivers - live countdown</span><span style="font-size:11px;color:var(--text2)">Auto-updates every 10s · Drivers auto-promoted to Standby on expiry</span></div>`;

  if(resting.length===0){html+=`<div style="background:#fff;border:1px solid var(--border);border-radius:var(--rl);padding:30px;text-align:center;color:var(--text3)">No drivers currently resting.</div>`;}
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
          <div style="font-size:10px;color:var(--text2);margin-top:2px">Started: ${c.restStarted?fmtTime(new Date(c.restStarted)):'-'}</div>
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
  html+=`<div style="background:#fff;border:1px solid var(--border);border-radius:var(--r);padding:12px;display:flex;align-items:center;gap:10px"><div style="width:12px;height:12px;border-radius:50%;background:${DEPOT_COLORS.Changamwe};flex-shrink:0"></div><div><div style="font-size:12px;font-weight:700">Home depot</div><div style="font-size:11px;color:var(--text2)">${HOME_REST_HOURS}h rest period</div></div></div>`;
  html+=`<div style="background:#fff;border:1px solid var(--border);border-radius:var(--r);padding:12px;display:flex;align-items:center;gap:10px"><div style="width:12px;height:12px;border-radius:50%;background:${DEPOT_COLORS.Mtito};flex-shrink:0"></div><div><div style="font-size:12px;font-weight:700">Away depot</div><div style="font-size:11px;color:var(--text2)">${AWAY_REST_HOURS}h rest period</div></div></div>`;
  html+=`</div>`;
  document.getElementById('pbody').innerHTML=html;
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
  document.getElementById('phSub').textContent=`${MONTH_NAME} - Daily Position Register`;
  document.getElementById('phActions').innerHTML=`
    ${currentUser.isHQ?`<select class="sel-sm no-print" onchange="setHqDepotView(this.value);renderMonthly()"><option value="all">All depots</option>${getActiveDepots().map(d=>`<option value="${d}"${hqDepotView===d?' selected':''}>${d}</option>`).join('')}</select>`:''}
    <button class="btn btn-ghost btn-sm no-print" onclick="window.print()">🖨 Print</button>
    <button class="btn btn-primary btn-sm no-print" onclick="exportMonthlyCSV()">⬇ CSV</button>`;

  let html=`<div class="legend">`;
  Object.entries(STATUS_META).forEach(([code,m])=>{html+=`<div class="leg-item"><div class="leg-dot" style="background:${m.bg};border:1px solid ${m.fg}50"></div><b>${code}</b> = ${m.label}</div>`;});
  html+=`</div><div class="month-wrap"><table class="month-tbl"><thead><tr>
    <th class="mc-name">Name</th>${showDepot?'<th class="mc-dep">Depot</th>':''}`;
  for(let d=1;d<=DAYS_IN_MON;d++){const dt=new Date(CY,CM,d);const we=dt.getDay()===0||dt.getDay()===6;const isTod=d===CD;html+=`<th style="${we?'color:#E53935':''}${isTod?';background:#FFF8E1;color:var(--kr-red)':''}"><div>${d}</div><div style="font-size:8px">${DAY_NAMES[dt.getDay()]}</div></th>`;}
  html+=`<th style="background:#E8F5E9;color:#1B5E20">BK</th><th style="background:#E3F2FD;color:#0D47A1">SB</th><th style="background:#F3E5F5;color:#4A148C">R</th><th style="background:#FFF3E0;color:#E65100">L</th><th style="background:#FFEBEE;color:#B71C1C">SK</th><th style="background:#ECEFF1;color:#37474F">NTB</th><th style="background:#FCE4EC;color:#AD1457">TO</th></tr></thead><tbody>`;

  allCrew.forEach((c,i)=>{
    const rowBg=i%2===0?'background:#F7F9FC':'';
    html+=`<tr style="${rowBg}"><td class="mc-name">${c.name}</td>`;
    if(showDepot)html+=`<td class="mc-dep" style="color:${DEPOT_COLORS[c.depot]};font-weight:700">${c.depot}</td>`;
    const sm={BK:0,SB:0,R:0,L:0,SK:0,T:0,NTB:0,TO:0};
    for(let d=1;d<=DAYS_IN_MON;d++){
      const key=`d${d}`;const code=(c.monthly&&c.monthly[key])||'';
      const dt=new Date(CY,CM,d);const we=dt.getDay()===0||dt.getDay()===6;
      const cls=code?`day-${code}`:(we?'day-we':'');const isTod=d===CD;
      if(sm[code]!==undefined)sm[code]++;
      const edAtt=!currentUser.isHQ?`class="${cls} day-ed${isTod?' today-col':''}" title="Click to edit" onclick="openDayEdit('${c.depot}','${c.id}',${d})"`:`class="${cls}${isTod?' today-col':''}"`;
      html+=`<td ${edAtt}>${code||''}</td>`;
    }
    html+=`<td class="mc-sBK">${sm.BK}</td><td class="mc-sSB">${sm.SB}</td><td class="mc-sR">${sm.R}</td><td class="mc-sL">${sm.L}</td><td class="mc-sSK">${sm.SK}</td><td class="mc-sNTB">${sm.NTB}</td><td class="mc-sTO">${sm.TO}</td></tr>`;
  });
  // Booked count row
  html+=`<tr style="background:#0F172A"><td class="mc-name" style="color:#E0E0E0;font-weight:700;font-size:10px">BOOKED / DAY</td>${showDepot?'<td style="background:#0F172A"></td>':''}`;
  for(let d=1;d<=DAYS_IN_MON;d++){const key=`d${d}`;const bk=allCrew.filter(c=>(c.monthly&&c.monthly[key])==='BK').length;html+=`<td style="background:${bk>0?'#1B5E20':'#B71C1C'};color:#fff;font-weight:700;font-size:11px">${bk}</td>`;}
  html+=`<td colspan="7" style="color:rgba(255,255,255,.4);font-size:10px;text-align:left;padding-left:8px">Booked per day</td></tr>`;
  html+=`</tbody></table></div>`;
  document.getElementById('pbody').innerHTML=html;
}

/* ════════ REPORTS ═════════════════════════════════════════════════════════ */
function renderReports(){
  document.getElementById('phSub').textContent='Export and download crew data';
  document.getElementById('phActions').innerHTML='';
  const monthOptions=getRecentMonthOptions(12);
  const depots=currentUser.isHQ?getActiveDepots():[currentUser.depot];
  const all=getAllCrew(state, depots);const c=cts(all);
  document.getElementById('pbody').innerHTML=`
  <div class="rep-grid">
    <div class="rep-card"><h3>📊 Daily Status Report</h3><p>Today's crew status - name, designation, depot, status, train type, route, shift, notes.</p><button class="btn btn-primary btn-sm" onclick="exportCSV()">⬇ Export CSV</button></div>
    <div class="rep-card"><h3>📅 Monthly Position Register</h3><p>Download current or previous month registers from Firestore archive.</p>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:10px">
        <select id="reportMonth" class="sel-sm no-print">${monthOptions}</select>
        <button class="btn btn-primary btn-sm" onclick="exportMonthlyCSV(document.getElementById('reportMonth').value)">⬇ Export Monthly CSV</button>
      </div>
    </div>
    <div class="rep-card"><h3>📈 Crew Utilization Report</h3><p>Booking frequency across the selected window.</p>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:10px">
        <select id="utilWindow" class="sel-sm no-print" onchange="updateReportSummary()">
          <option value="month">This month</option>
          <option value="quarter">Last quarter</option>
          <option value="90d">Last 90 days</option>
          <option value="180d">Last 180 days</option>
          <option value="365d">Last 365 days</option>
        </select>
        <button class="btn btn-primary btn-sm" onclick="exportUtilizationCSV(document.getElementById('utilWindow').value)">⬇ Export Utilization CSV</button>
      </div>
      <div id="utilSummary" class="rep-note" style="margin-top:12px;color:var(--text2)">Choose a time window to preview utilization metrics.</div>
    </div>
    <div class="rep-card"><h3>🖨 Print Register</h3><p>Clean printable monthly position register for notice boards.</p><button class="btn btn-ghost btn-sm" onclick="goPage('monthly');setTimeout(()=>window.print(),500)">🖨 Print</button></div>
    <div class="rep-card"><h3>⚠ Absence &amp; NTB Report</h3><p>All crew on sick leave, annual leave, or marked Not to be Booked.</p><button class="btn btn-primary btn-sm" onclick="exportAbsenceCSV()">⬇ Export CSV</button></div>
  </div>
  <hr class="divider">
  <div class="sec-hdr"><span class="sec-title">${MONTH_NAME} - snapshot</span></div>
  ${kpiHtml([['TOT','Total crew',all.length],['BK','Booked',c.BK||0],['SB','Standby',c.SB||0],['R','Resting',c.R||0],['L','On Leave',c.L||0],['SK','Sick',c.SK||0],['T','Training',c.T||0],['NTB','NTB',c.NTB||0],['TO','Trip Off',c.TO||0]])}`;
  updateReportSummary();
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
  const depots=currentUser.isHQ?getActiveDepots():[currentUser.depot];
  if(monthKey===MONTH_KEY) return getAllCrew(state, depots);
  if(!db) return [];
  const list=[];
  for(const depot of depots){
    const snap = await getDocs(query(collection(db,'crew'), where('depot','==',depot), where('monthKey','==',monthKey)));
    snap.forEach(docSnap=>{list.push(docSnap.data());});
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
  const cols = Math.max(...crew.map(c=>Object.keys(c.monthly||{}).length), 0);
  let hdr='ID,Name,Designation,Depot';
  for(let i=1;i<=cols;i++) hdr+=`,${i}`;
  hdr+=',BK,SB,R,L,SK,T,NTB,TO\n';
  let csv=hdr;
  crew.sort((a,b)=>a.name.localeCompare(b.name)).forEach(c=>{
    let row=`"${c.id}","${c.name}","${getDesignationLabel(c.grade)}","${c.depot}"`;
    const counts={BK:0,SB:0,R:0,L:0,SK:0,T:0,NTB:0,TO:0};
    for(let i=1;i<=cols;i++){
      const code=(c.monthly&&c.monthly[`d${i}`])||'';
      if(counts[code]!==undefined) counts[code]++;
      row+=`,"${code}"`;
    }
    row+=`,`+counts.BK+','+counts.SB+','+counts.R+','+counts.L+','+counts.SK+','+counts.T+','+counts.NTB+','+counts.TO+'\n';
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
      for(let d=1;d<=days;d++) if((c.monthly&&c.monthly[`d${d}`])==='BK') existing.bookedDays++;
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
      for(let d=1;d<=getDaysInMonthKey(monthKey);d++) if((c.monthly&&c.monthly[`d${d}`])==='BK') sum++;
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
  renderAdmin();
}

async function loadAdminUsers(){
  if(!db) return [];
  const snap=await getDocs(collection(db,'users'));
  const users=[];
  snap.forEach(docSnap=>{users.push({username:docSnap.id,...docSnap.data()});});
  users.sort((a,b)=>String(a.username).localeCompare(String(b.username)));
  return users;
}

async function renderAdmin(){
  document.getElementById('phSub').textContent='Manage Firestore-backed configuration';
  document.getElementById('phActions').innerHTML=hasGlobalAccess()?'<button class="btn btn-ghost btn-sm no-print" onclick="reloadAdminData()">Reload</button>':'';
  if(!hasGlobalAccess()){
    document.getElementById('pbody').innerHTML='<div style="background:#fff;border:1px solid var(--border);border-radius:var(--r);padding:18px;color:var(--text2)">Admin tools are available to HQ users only.</div>';
    return;
  }
  const users=await loadAdminUsers();
  const depotRows=getActiveDepots().map(depot=>{
    const color=DEPOT_COLORS[depot]||'#37474F';
    const hours=REST_HOURS[depot]||12;
    return `<div class="admin-row" style="display:grid;grid-template-columns:1fr 1.2fr 100px 80px 80px 70px;gap:8px;align-items:center;margin-bottom:8px">
      <input value="${depot}" data-admin-depot-id="${depot}" style="padding:7px 9px;border:1px solid var(--border);border-radius:var(--r)" placeholder="Depot id">
      <input value="${depot}" data-admin-depot-label="${depot}" style="padding:7px 9px;border:1px solid var(--border);border-radius:var(--r)" placeholder="Label">
      <input value="${color}" data-admin-depot-color="${depot}" style="padding:7px 9px;border:1px solid var(--border);border-radius:var(--r)" placeholder="#color">
      <input type="number" value="${hours}" min="1" data-admin-depot-hours="${depot}" style="padding:7px 9px;border:1px solid var(--border);border-radius:var(--r)" placeholder="Hours">
      <label style="font-size:12px;display:flex;align-items:center;gap:6px"><input type="checkbox" checked data-admin-depot-active="${depot}"> Active</label>
      <button class="btn btn-primary btn-sm" onclick="saveDepotMetaRecord('${depot}')">Save</button>
    </div>`;
  }).join('');

  const designationRows=Object.values(DEFAULT_DESIGNATION_REGISTRY).map(meta=>{
    const aliases=(meta.aliases||[]).join(', ');
    return `<div class="admin-row" style="display:grid;grid-template-columns:1fr 1.5fr 90px 70px;gap:8px;align-items:center;margin-bottom:8px">
      <input value="${meta.id}" data-admin-desig-id="${meta.id}" style="padding:7px 9px;border:1px solid var(--border);border-radius:var(--r)" placeholder="Designation id">
      <input value="${meta.label}" data-admin-desig-label="${meta.id}" style="padding:7px 9px;border:1px solid var(--border);border-radius:var(--r)" placeholder="Label">
      <label style="font-size:12px;display:flex;align-items:center;gap:6px"><input type="checkbox" ${meta.restEligible?'checked':''} data-admin-desig-rest="${meta.id}"> Rest</label>
      <button class="btn btn-primary btn-sm" onclick="saveDesignationMetaRecord('${meta.id}')">Save</button>
      <div style="grid-column:1 / -1;font-size:11px;color:var(--text2)">Aliases: ${aliases||'-'}</div>
    </div>`;
  }).join('');

  const userRows=users.map(user=>{
    const userRole=user.role||'booking_officer';
    const roleOptions=USER_ROLE_OPTIONS.map(role=>`<option value="${role.id}"${role.id===userRole?' selected':''}>${role.label}</option>`).join('');
    return `<div class="admin-row" style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr 1fr 80px;gap:8px;align-items:center;margin-bottom:8px">
      <input value="${user.username}" disabled style="padding:7px 9px;border:1px solid var(--border);border-radius:var(--r);background:#F7F9FC" placeholder="Username">
      <input value="${user.name||''}" data-admin-user-name="${user.username}" style="padding:7px 9px;border:1px solid var(--border);border-radius:var(--r)" placeholder="Display name">
      <input value="${user.depot||''}" data-admin-user-depot="${user.username}" style="padding:7px 9px;border:1px solid var(--border);border-radius:var(--r)" placeholder="Depot">
      <select data-admin-user-role="${user.username}" style="padding:7px 9px;border:1px solid var(--border);border-radius:var(--r)">${roleOptions}</select>
      <input type="password" value="${user.pw||''}" data-admin-user-pw="${user.username}" style="padding:7px 9px;border:1px solid var(--border);border-radius:var(--r)" placeholder="Password">
      <button class="btn btn-primary btn-sm" onclick="saveUserAccount('${user.username}')">Save</button>
      <label style="grid-column:1 / -1;font-size:12px;display:flex;align-items:center;gap:6px"><input type="checkbox" ${user.isHQ?'checked':''} data-admin-user-hq="${user.username}"> HQ / global access</label>
    </div>`;
  }).join('');

  document.getElementById('pbody').innerHTML=`
    <div style="display:grid;gap:14px">
      <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r);padding:14px">
        <div style="font-size:14px;font-weight:800;margin-bottom:4px">Firestore maintenance</div>
        <div style="font-size:12px;color:var(--text2);margin-bottom:10px">Seed or refresh the data collections that drive the crew app.</div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <button class="btn btn-primary" onclick="seedFirestore()">Seed / refresh Firestore</button>
          <button class="btn btn-ghost" onclick="reloadAdminData()">Reload admin data</button>
        </div>
      </div>
      <div class="admin-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:14px">
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r);padding:14px">
          <div style="font-size:13px;font-weight:800;margin-bottom:8px">Depots</div>
          <div style="display:grid;grid-template-columns:1fr 1.2fr 100px 80px 80px 70px;gap:8px;font-size:11px;color:var(--text2);margin-bottom:8px">
            <div>ID</div><div>Label</div><div>Color</div><div>Hours</div><div>Active</div><div></div>
          </div>
          ${depotRows}
        </div>
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r);padding:14px">
          <div style="font-size:13px;font-weight:800;margin-bottom:8px">Designations</div>
          <div style="display:grid;grid-template-columns:1fr 1.5fr 90px 70px;gap:8px;font-size:11px;color:var(--text2);margin-bottom:8px">
            <div>ID</div><div>Label</div><div>Rest</div><div></div>
          </div>
          ${designationRows}
        </div>
      </div>
      <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r);padding:14px">
        <div style="font-size:13px;font-weight:800;margin-bottom:6px">Crew upload options</div>
        <div style="font-size:12px;color:var(--text2)">Use the Add Crew modal for quick bulk paste. A CSV import flow can be added next if you want file-based uploads.</div>
      </div>
      <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r);padding:14px">
        <div style="font-size:13px;font-weight:800;margin-bottom:8px">Users</div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr 1fr 80px;gap:8px;font-size:11px;color:var(--text2);margin-bottom:8px">
          <div>Username</div><div>Name</div><div>Depot</div><div>Role</div><div>Password</div><div></div>
        </div>
        ${userRows}
      </div>
    </div>`;
}

async function saveDepotMetaRecord(depotId){
  if(!db) return;
  const idEl=document.querySelector(`[data-admin-depot-id="${depotId}"]`);
  const labelEl=document.querySelector(`[data-admin-depot-label="${depotId}"]`);
  const colorEl=document.querySelector(`[data-admin-depot-color="${depotId}"]`);
  const hoursEl=document.querySelector(`[data-admin-depot-hours="${depotId}"]`);
  const activeEl=document.querySelector(`[data-admin-depot-active="${depotId}"]`);
  const nextId=(idEl?.value||depotId).trim();
  const meta={
    id:nextId,
    label:(labelEl?.value||nextId).trim(),
    color:(colorEl?.value||'#37474F').trim(),
    restHours:Number(hoursEl?.value||12),
    order:getActiveDepots().indexOf(depotId)+1,
    active:!!activeEl?.checked,
  };
  await setDoc(doc(db,'depotMeta',nextId),meta,{merge:true});
  await loadDepotMeta();
  renderAdmin();
}

async function saveDesignationMetaRecord(designationId){
  if(!db) return;
  const labelEl=document.querySelector(`[data-admin-desig-label="${designationId}"]`);
  const restEl=document.querySelector(`[data-admin-desig-rest="${designationId}"]`);
  const meta={
    id:designationId,
    label:(labelEl?.value||designationId).trim(),
    aliases:DEFAULT_DESIGNATION_REGISTRY[designationId]?.aliases||[],
    restEligible:!!restEl?.checked,
    order:DEFAULT_DESIGNATION_REGISTRY[designationId]?.order??999,
  };
  await setDoc(doc(db,'designationMeta',designationId),meta,{merge:true});
  await loadDesignationMeta();
  renderAdmin();
}

async function saveUserAccount(username){
  if(!db) return;
  const nameEl=document.querySelector(`[data-admin-user-name="${username}"]`);
  const depotEl=document.querySelector(`[data-admin-user-depot="${username}"]`);
  const roleEl=document.querySelector(`[data-admin-user-role="${username}"]`);
  const pwEl=document.querySelector(`[data-admin-user-pw="${username}"]`);
  const hqEl=document.querySelector(`[data-admin-user-hq="${username}"]`);
  const role=roleEl?.value||'booking_officer';
  const isHQ=!!hqEl?.checked || role==='super_admin' || role==='hq_admin';
  const payload={
    username,
    name:(nameEl?.value||username).trim(),
    depot:(depotEl?.value||'HQ').trim(),
    role,
    pw:(pwEl?.value||'').trim(),
    isHQ,
    isSuperAdmin:role==='super_admin',
  };
  await setDoc(doc(db,'users',username),payload,{merge:true});
  await renderAdmin();
}

/* ════════ MODALS ══════════════════════════════════════════════════════════ */
function onStatusChange(){
  const s=document.getElementById('mStatus').value;
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
  if(info) info.textContent=`${homeDepot} depot - ${HOME_REST_HOURS}h rest period when resting at home.`;
  const loc=document.getElementById('mRestLocation').value;
  if(loc==='away'){
    const away=document.getElementById('mAwayDepot')?.value||'selected away depot';
    if(hint) hint.textContent=`Away depot rest is ${AWAY_REST_HOURS}h. Current away depot: ${away}.`;
  } else {
    if(hint) hint.textContent=`Home depot rest is ${HOME_REST_HOURS}h.`;
  }
}

function setAwayDepotOptions(homeDepot,selectedAway=''){
  const awaySelect=document.getElementById('mAwayDepot');
  if(!awaySelect) return;
  awaySelect.innerHTML=getActiveDepots().filter(d=>d!==homeDepot).map(d=>`<option value="${d}"${d===selectedAway?' selected':''}>${d}</option>`).join('');
}

function openUpdate(depot,id){
  editKey={depot,id,day:null};
  const c=Object.values(state[depot]||{}).find(x=>x.id===id);if(!c)return;
  currentModalGrade=c.grade;
  populateStatusSelects(c.status||'SB');
  document.getElementById('mTitle').textContent='Update crew status';
  document.getElementById('mSub').textContent=`${c.name} · ${c.id} · ${depot}`;
  document.getElementById('mStatus').value=c.status||'SB';
  document.getElementById('mTrainType').value=c.trainType||'';
  document.getElementById('mBookTime').value=c.bookTime||'';
  document.getElementById('mRoute').value=c.route||'';
  document.getElementById('mShift').value=c.shift||'Day (06:00–14:00)';
  document.getElementById('mNotes').value=c.notes||'';
  if(c.restStarted){
    const restDate = c.restStarted && c.restStarted.toDate ? c.restStarted.toDate() : new Date(c.restStarted);
    document.getElementById('mRestStart').value=isNaN(restDate.getTime())?new Date().toTimeString().substring(0,5):restDate.toTimeString().substring(0,5);
  } else document.getElementById('mRestStart').value=new Date().toTimeString().substring(0,5);
  document.getElementById('mRestLocation').value=c.awayDepot && c.awayDepot!==depot?'away':'home';
  setAwayDepotOptions(depot,c.awayDepot);
  onStatusChange();
  document.getElementById('mRemoveBtn').style.display=(!hasGlobalAccess())?'inline-flex':'none';
  document.getElementById('modal').classList.add('open');
}

function openDayEdit(depot,id,day){
  editKey={depot,id,day};
  const c=Object.values(state[depot]||{}).find(x=>x.id===id);if(!c)return;
  currentModalGrade=c.grade;
  populateStatusSelects((c.monthly&&c.monthly[`d${day}`])||'SB');
  const dt=new Date(CY,CM,day);
  document.getElementById('mTitle').textContent='Edit daily position';
  document.getElementById('mSub').textContent=`${c.name} · ${DAY_NAMES[dt.getDay()]} ${day} ${MONTH_NAME.split(' ')[0]}`;
  document.getElementById('mStatus').value=(c.monthly&&c.monthly[`d${day}`])||'SB';
  document.getElementById('mTrainType').value='';document.getElementById('mBookTime').value='';
  document.getElementById('mRoute').value=c.route||'';
  document.getElementById('mShift').value=c.shift||'Day (06:00–14:00)';document.getElementById('mNotes').value='';
  document.getElementById('mRestStart').value=new Date().toTimeString().substring(0,5);
  document.getElementById('mRestLocation').value='home';
  setAwayDepotOptions(depot,'');
  onStatusChange();
  document.getElementById('mRemoveBtn').style.display='none';
  updateStatusValidation();
  document.getElementById('modal').classList.add('open');
}

function closeModal(){document.getElementById('modal').classList.remove('open');editKey=null;currentModalGrade=null;}

async function saveModal(){
  if(!editKey)return;
  setSyncStatus('spin','Saving…');
  try{
    const newStatus=document.getElementById('mStatus').value;
    const trainType=newStatus==='BK'?document.getElementById('mTrainType').value:'';
    const bookTime=newStatus==='BK'?document.getElementById('mBookTime').value:'';
    const restStartInput=document.getElementById('mRestStart').value;
    if(newStatus==='R' && !isRestAllowedForGrade(currentModalGrade)){
<<<<<<< HEAD
      alert('Resting can only be applied to locomotive driver designations. Change the status before saving.');
=======
      alert('Resting can only be applied to Locomotive driver designation. Change the status before saving.');
>>>>>>> e4f6718b4073d665798519b29bb92e2b448f6451
      return;
    }

    if(editKey.day){
      const c=Object.values(state[editKey.depot]||{}).find(x=>x.id===editKey.id);if(!c){closeModal();return;}
      const monthly={...(c.monthly||{})};monthly[`d${editKey.day}`]=newStatus;
      const restLocation=document.getElementById('mRestLocation').value;
      const awayDepot = newStatus==='R' && restLocation==='away' ? document.getElementById('mAwayDepot')?.value||null : null;
      const upd={monthly,awayDepot};
      if(editKey.day===CD){upd.status=newStatus;upd.trainType=trainType;upd.bookTime=bookTime;upd.since=fmtTime(new Date());upd.updatedBy=currentUser.username;if(newStatus==='R'&&restStartInput){const[hh,mm]=restStartInput.split(':');const rs=new Date();rs.setHours(parseInt(hh),parseInt(mm),0,0);upd.restStarted=rs.toISOString();}else if(newStatus!=='R')upd.restStarted=null;}
      await writeCrewDoc(editKey.depot,editKey.id,upd);
      setLog(`${c.name} Day ${editKey.day} → ${STATUS_META[newStatus]?.label}`);
    } else {
      const c=Object.values(state[editKey.depot]||{}).find(x=>x.id===editKey.id);if(!c){closeModal();return;}
      const monthly={...(c.monthly||{})};monthly[`d${CD}`]=newStatus;
      let restStarted=c.restStarted||null;
      if(newStatus==='R'&&restStartInput){const[hh,mm]=restStartInput.split(':');const rs=new Date();rs.setHours(parseInt(hh),parseInt(mm),0,0);restStarted=rs.toISOString();}
      else if(newStatus!=='R')restStarted=null;
      const restLocation=document.getElementById('mRestLocation').value;
      const awayDepot = newStatus==='R' && restLocation==='away' ? document.getElementById('mAwayDepot')?.value||null : null;
      const upd={status:newStatus,trainType,bookTime,route:document.getElementById('mRoute').value||c.route,shift:document.getElementById('mShift').value,notes:document.getElementById('mNotes').value,since:fmtTime(new Date()),updatedBy:currentUser.username,restStarted,monthly,awayDepot: newStatus==='R'?awayDepot:null};
      await writeCrewDoc(editKey.depot,editKey.id,upd);
      setLog(`${c.name}: ${STATUS_META[c.status]?.label} → ${STATUS_META[newStatus]?.label}${trainType?' ('+trainType+')':''}${bookTime?' @ '+bookTime:''}`);
    }
    setSyncStatus('ok','Saved');
  }catch(err){setSyncStatus('err','Save failed');setLog('Error: '+err.message);}
  closeModal();
  if(!db)refreshPage();
}

/* ════════ REMOVE CREW ═════════════════════════════════════════════════════ */
async function confirmRemoveCrew(){
  if(!editKey)return;
  const c=Object.values(state[editKey.depot]||{}).find(x=>x.id===editKey.id);
  if(!c)return;
  if(!confirm(`Remove ${c.name} (${c.id}) from ${c.depot}?\n\nThis cannot be undone.`))return;
  closeModal();
  setSyncStatus('spin','Removing…');
  try{
    await removeCrewDoc(editKey.depot,editKey.id);
    setLog(`${c.name} (${c.id}) removed from ${c.depot}.`);
    setSyncStatus('ok','Removed');
  }catch(err){setSyncStatus('err','Remove failed');setLog('Error: '+err.message);}
  if(!db)refreshPage();
}

async function removeCrewDoc(depot,id){
  if(demoMode||!db){
    if(state[depot])delete state[depot][id];
    return;
  }
  await deleteDoc(doc(db,'crew',`${depot}_${id}`));
}

/* ════════ ADD CREW ════════════════════════════════════════════════════════ */
function openAddModal(){
  document.getElementById('addModalSub').textContent='Depot: '+(currentUser.depot==='HQ'?'Select below':currentUser.depot);
  document.getElementById('addName').value='';document.getElementById('addRoute').value='';
  const depotSelect=document.getElementById('addDepot');
  if(depotSelect){
    depotSelect.parentElement.style.display=currentUser.isHQ?'block':'none';
    depotSelect.innerHTML=getActiveDepots().map(d=>`<option value="${d}"${d===(hqDepotView!=='all'?hqDepotView:getActiveDepots()[0])?' selected':''}>${d}</option>`).join('');
  }
  populateDesignationSelect();
  switchAddTab('single');
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

async function saveAddCrew(){
  const depot=currentUser.isHQ?(document.getElementById('addDepot')?.value||hqDepotView):currentUser.depot;
  if(depot==='all'||depot==='HQ'||!depot){alert('Please select a specific depot first.');return;}
  const isBulk=document.getElementById('addBulk').style.display!=='none';
  if(!isBulk){
    const grade=document.getElementById('addGrade').value;
    const initStatus=document.getElementById('addStatus').value;
    if(initStatus==='R' && !isRestAllowedForGrade(grade)){
<<<<<<< HEAD
      alert('Only locomotive driver designations may be added with Resting status. Please choose another status.');
=======
      alert('Only Locomotive driver may be added with Resting status. Please choose another status.');
>>>>>>> e4f6718b4073d665798519b29bb92e2b448f6451
      return;
    }
  }
  setSyncStatus('spin','Adding…');
  try{
    if(isBulk){
      const lines=document.getElementById('bulkText').value.split('\n').map(l=>l.trim()).filter(l=>l);
      let added=0;
      for(const line of lines){
        const parts=line.split(',').map(p=>p.trim());
        const name=parts[0];if(!name)continue;
<<<<<<< HEAD
        const grade=parts[1]||'locomotive_driver';const route=parts[2]||'';
=======
        const grade=parts[1]||'Locomotive driver';const route=parts[2]||'';
>>>>>>> e4f6718b4073d665798519b29bb92e2b448f6451
        await addSingleCrew(depot,name,grade,route,'SB');added++;
      }
      setLog(`${added} crew member(s) added to ${depot}.`);
    } else {
      const name=document.getElementById('addName').value.trim();
      if(!name){alert('Please enter a name.');return;}
      await addSingleCrew(depot,name,document.getElementById('addGrade').value,document.getElementById('addRoute').value,document.getElementById('addStatus').value);
      setLog(`${name} added to ${depot}.`);
    }
    setSyncStatus('ok','Saved');
  }catch(err){setSyncStatus('err','Failed');setLog('Error: '+err.message);}
  closeAddModal();
  if(!db)refreshPage();
}

async function addSingleCrew(depot,name,grade,route,initStatus){
  const existing=Object.values(state[depot]||{}).map(c=>c.id);
  const prefix=depot.substring(0,2).toUpperCase();
  let num=1;while(existing.includes(`${prefix}-${String(num).padStart(3,'0')}`))num++;
  const id=`${prefix}-${String(num).padStart(3,'0')}`;
  const monthly={};for(let d=1;d<=DAYS_IN_MON;d++)monthly[`d${d}`]='';monthly[`d${CD}`]=initStatus;
  const obj={id,name,grade:normalizeDesignation(grade),depot,route,shift:'Day (06:00-14:00)',status:initStatus,trainType:'',notes:'',since:fmtTime(new Date()),monthly,restStarted:null,awayDepot:null,updatedBy:currentUser.username,monthKey:MONTH_KEY};
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
<<<<<<< HEAD
  const d=currentUser.isHQ?getActiveDepots():[currentUser.depot];const all=getAllCrew(state, d);
=======
  const d=currentUser.isHQ?DEPOTS:[currentUser.depot];const all=getAllCrew(state, d);
>>>>>>> e4f6718b4073d665798519b29bb92e2b448f6451
  let csv='ID,Name,Designation,Depot,Route,Shift,Status,Train Type,Booked Time,Rest Remaining,Since,Notes\n';
  all.forEach(c=>{
    const sec=restSecondsLeft(c);const rem=sec!==null&&sec>0?fmtCountdown(sec):(sec===0?'Complete':'-');
    csv+=`"${c.id}","${c.name}","${getDesignationLabel(c.grade)}","${c.depot}","${c.route||''}","${c.shift||''}","${STATUS_META[c.status]?.label||c.status}","${c.trainType||''}","${c.status==='BK'&&c.bookTime?c.bookTime:''}","${rem}","${c.since||''}","${(c.notes||'').replace(/"/g,"'")}"\n`;
  });
  dlCSV(csv,`KR_Status_${todayStr()}.csv`);
}
function exportMonthlyCSVLegacy(){
<<<<<<< HEAD
  const d=currentUser.isHQ?getActiveDepots():[currentUser.depot];const all=getAllCrew(state, d).sort((a,b)=>a.name.localeCompare(b.name));
=======
  const d=currentUser.isHQ?DEPOTS:[currentUser.depot];const all=getAllCrew(state, d).sort((a,b)=>a.name.localeCompare(b.name));
>>>>>>> e4f6718b4073d665798519b29bb92e2b448f6451
  let hdr='ID,Name,Designation,Depot';for(let i=1;i<=DAYS_IN_MON;i++)hdr+=`,${i}`;hdr+=',BK,SB,R,L,SK,T,NTB,TO\n';
  let csv=hdr;
  all.forEach(c=>{let row=`"${c.id}","${c.name}","${getDesignationLabel(c.grade)}","${c.depot}"`;const sm={BK:0,SB:0,R:0,L:0,SK:0,T:0,NTB:0,TO:0};for(let i=1;i<=DAYS_IN_MON;i++){const code=(c.monthly&&c.monthly[`d${i}`])||'';if(sm[code]!==undefined)sm[code]++;row+=`,"${code}"`;}row+=`,${sm.BK},${sm.SB},${sm.R},${sm.L},${sm.SK},${sm.T},${sm.NTB},${sm.TO}`;csv+=row+'\n';});
  dlCSV(csv,`KR_Monthly_${MONTH_NAME.replace(' ','_')}.csv`);
}
function exportAbsenceCSV(){
<<<<<<< HEAD
  const d=currentUser.isHQ?getActiveDepots():[currentUser.depot];const all=getAllCrew(state, d).filter(c=>['SK','L','NTB'].includes(c.status));
  let csv='ID,Name,Designation,Depot,Status,NTB Reason/Notes,Last Updated\n';
  all.forEach(c=>{csv+=`"${c.id}","${c.name}","${getDesignationLabel(c.grade)}","${c.depot}","${STATUS_META[c.status]?.label}","${(c.notes||'').replace(/"/g,"'")}","${c.lastUpdated||''}"\n`;});
=======
  const d=currentUser.isHQ?DEPOTS:[currentUser.depot];const all=getAllCrew(state, d).filter(c=>['SK','L','NTB'].includes(c.status));
  let csv='ID,Name,Designation,Depot,Status,NTB Reason/Notes,Last Updated\n';
  all.forEach(c=>{csv+=`"${c.id}","${c.name}","${c.grade}","${c.depot}","${STATUS_META[c.status]?.label}","${(c.notes||'').replace(/"/g,"'")}","${c.lastUpdated||''}"\n`;});
>>>>>>> e4f6718b4073d665798519b29bb92e2b448f6451
  dlCSV(csv,`KR_Absences_${todayStr()}.csv`);
}
window.doLogin = doLogin;
window.useDemoMode = useDemoMode;
window.doLogout = doLogout;
window.goPage = goPage;
window.closeModal = closeModal;
window.confirmRemoveCrew = confirmRemoveCrew;
window.saveModal = saveModal;
window.switchAddTab = switchAddTab;
window.closeAddModal = closeAddModal;
window.saveAddCrew = saveAddCrew;
window.openUpdate = openUpdate;
window.openDayEdit = openDayEdit;
window.onStatusChange = onStatusChange;
window.exportCSV = exportCSV;
window.exportMonthlyCSV = exportMonthlyCSV;
window.exportUtilizationCSV = exportUtilizationCSV;
window.updateReportSummary = updateReportSummary;
window.exportAbsenceCSV = exportAbsenceCSV;
window.setFilter = setFilter;
window.filterSearch = filterSearch;
window.setHqDepotView = setHqDepotView;
window.reloadAdminData = reloadAdminData;
window.renderAdmin = renderAdmin;
window.saveDepotMetaRecord = saveDepotMetaRecord;
window.saveDesignationMetaRecord = saveDesignationMetaRecord;
window.saveUserAccount = saveUserAccount;
window.seedFirestore = async () => {
  if(!db){setLog('Cannot seed Firestore: database not initialized.');return;}
  await seedDepotMetaIfEmpty();
  await loadDepotMeta();
  await seedUsersIfEmpty();
  await ensureCoreAccessUsers();
  await seedStatusMetaIfEmpty();
  await loadStatusMeta();
  await seedDesignationMetaIfEmpty();
  await loadDesignationMeta();
  await migrateCrewDesignationKeys();
  const depots = getActiveDepots();
  await Promise.all(depots.map(seedDepotIfEmpty));
  await ensureRestCountdownSample();
  setLog('Firestore seed complete.');
};
/* ════════ UI ═══════════════════════════════════════════════════════════════ */

document.addEventListener('keydown',e=>{if(e.key==='Escape'){closeModal();closeAddModal();}});
['lUser','lPass'].forEach(id=>{document.getElementById(id)?.addEventListener('keydown',e=>{if(e.key==='Enter')doLogin();});});

async function bootApp(){
  const cfg = await loadFirebaseConfig();
  if(cfg && cfg.apiKey && cfg.projectId){
    const ok = initFirebase(cfg);
    if(ok){
      await seedFirestoreUsers();
      const hasSession = await restoreSession();
      if(!hasSession){
        document.getElementById('loginPage').classList.add('show');
      }
      setSyncStatus('ok','Firebase connected');
      setLoginHint(false);
      setLog(hasSession?'Firebase connected - session restored.':'Firebase connected - ready.');
    } else {
      document.getElementById('loginPage').classList.add('show');
      setSyncStatus('err','Firebase initialization failed');
      setLoginHint(false);
      const errEl = document.getElementById('loginErr');
      if(errEl) errEl.textContent = 'Firebase init failed. Check console for details.';
    }
  } else {
    document.getElementById('loginPage').classList.add('show');
    setSyncStatus('err','Missing .env Firebase config');
    setLoginHint(false);
    const errEl = document.getElementById('loginErr');
    if(errEl) errEl.textContent = 'Firebase .env config not found. Create a .env file and reload.';
  }
}

if (document.readyState === 'loading') {
  window.addEventListener('DOMContentLoaded', bootApp);
} else {
  bootApp();
}

