export const DEFAULT_DEPOTS=['Changamwe','Mtito','Makadara','Nakuru','Kisumu','Eldoret'];
export const DEFAULT_DEPOT_META={
  Changamwe:{id:'Changamwe',label:'Changamwe',color:'#1B5E20',restHours:12,order:1,active:true},
  Mtito:{id:'Mtito',label:'Mtito',color:'#0D47A1',restHours:10,order:2,active:true},
  Makadara:{id:'Makadara',label:'Makadara',color:'#4A148C',restHours:12,order:3,active:true},
  Nakuru:{id:'Nakuru',label:'Nakuru',color:'#B71C1C',restHours:10,order:4,active:true},
  Kisumu:{id:'Kisumu',label:'Kisumu',color:'#E65100',restHours:12,order:5,active:true},
  Eldoret:{id:'Eldoret',label:'Eldoret',color:'#37474F',restHours:12,order:6,active:true},
  Malaba:{id:'Malaba',label:'Malaba',color:'#00695C',restHours:10,order:7,active:false},
  Sagana:{id:'Sagana',label:'Sagana',color:'#8E24AA',restHours:10,order:8,active:false},
};
export const DEPOTS=[...DEFAULT_DEPOTS];
export const DEPOT_COLORS=Object.fromEntries(Object.values(DEFAULT_DEPOT_META).map(meta=>[meta.id,meta.color]));
export const REST_HOURS=Object.fromEntries(Object.values(DEFAULT_DEPOT_META).map(meta=>[meta.id,meta.restHours]));
export const STATUS_META={
  BK:{label:'Booked',    bg:'#E8F5E9',fg:'#1B5E20'},
  SB:{label:'Standby',   bg:'#E3F2FD',fg:'#0D47A1'},
  R: {label:'Resting',   bg:'#F3E5F5',fg:'#4A148C'},
  L: {label:'On Leave',  bg:'#FFF3E0',fg:'#E65100'},
  SK:{label:'Sick',      bg:'#FFEBEE',fg:'#B71C1C'},
  T: {label:'Training',  bg:'#E0F2F1',fg:'#00695C'},
  NTB:{label:'NTB',      bg:'#ECEFF1',fg:'#37474F'},
  TO:{label:'Trip Off',  bg:'#FCE4EC',fg:'#AD1457'},
};
export const TRAIN_TYPES=['Freight','Commuter','Passenger','Engineering','Shunting'];
<<<<<<< HEAD
export const DEFAULT_DESIGNATION_REGISTRY={
  locomotive_driver:{id:'locomotive_driver',label:'Locomotive driver',aliases:['Driver A','Driver B'],restEligible:true,order:1},
  train_guard:{id:'train_guard',label:'train Guard',aliases:['Guard'],restEligible:false,order:2},
  shunter_driver:{id:'shunter_driver',label:'Shunter driver',aliases:['Shunter'],restEligible:false,order:3},
  locomotive_inspecting_officer:{id:'locomotive_inspecting_officer',label:'Locomotive Inspecting Officer(LIO)',aliases:['Technician'],restEligible:false,order:4},
  station_master:{id:'station_master',label:'station Master',aliases:['Station Master'],restEligible:false,order:5},
};
let designationRegistry={...DEFAULT_DESIGNATION_REGISTRY};
function normalizeDesignationKey(value){
  return String(value||'')
    .trim()
    .toLowerCase()
    .replace(/['’]/g,'')
    .replace(/[^a-z0-9]+/g,'_')
    .replace(/^_+|_+$/g,'');
}
function cloneDesignationRegistry(registry){
  const next={};
  Object.values(registry||{}).forEach((item,index)=>{
    if(!item) return;
    const id = item.id || normalizeDesignationKey(item.label) || `designation_${index+1}`;
    const aliases = Array.isArray(item.aliases)
      ? item.aliases.map(alias=>String(alias).trim()).filter(Boolean)
      : typeof item.aliases==='string'
        ? item.aliases.split(',').map(alias=>alias.trim()).filter(Boolean)
        : [];
    next[id]={
      id,
      label:String(item.label||id).trim()||id,
      aliases,
      restEligible:item.restEligible!==false,
      order:typeof item.order==='number'?item.order:index,
    };
  });
  return next;
}
export function setDesignationRegistry(registry){
  designationRegistry=Object.keys(registry||{}).length?cloneDesignationRegistry(registry):{...DEFAULT_DESIGNATION_REGISTRY};
}
export function getDesignationRegistry(){
  return designationRegistry;
}
export function normalizeDesignation(value){
  if(value===undefined||value===null) return '';
  const raw=String(value).trim();
  if(!raw) return '';
  const lower=raw.toLowerCase();
  const slug=normalizeDesignationKey(raw);
  if(designationRegistry[raw]) return raw;
  for(const [id,meta] of Object.entries(designationRegistry)){
    if(id.toLowerCase()===lower || id===slug) return id;
    if(String(meta.label||'').trim().toLowerCase()===lower) return id;
    if((meta.aliases||[]).some(alias=>String(alias).trim().toLowerCase()===lower)) return id;
  }
  return raw;
}
export function getDesignationLabel(value){
  const key=normalizeDesignation(value);
  return designationRegistry[key]?.label||String(value||'');
}
export function isDesignationRestEligible(value){
  const key=normalizeDesignation(value);
  return !!designationRegistry[key]?.restEligible;
}
export function getDesignationOptions(selected='locomotive_driver'){
  const selectedKey=normalizeDesignation(selected);
  return Object.values(designationRegistry)
    .sort((a,b)=>(a.order??999)-(b.order??999)||a.label.localeCompare(b.label))
    .map(meta=>`<option value="${meta.id}"${meta.id===selectedKey?' selected':''}>${meta.label}</option>`)
    .join('');
}
export const DRIVER_GRADES=['locomotive_driver'];
=======
export const DRIVER_GRADES=['Locomotive driver'];
>>>>>>> e4f6718b4073d665798519b29bb92e2b448f6451
export const STATUSES=['BK','SB','R','L','SK','T','NTB','TO'];
export const AVT_PAL=[['#E8F5E9','#1B5E20'],['#E3F2FD','#0D47A1'],['#FFF3E0','#E65100'],['#F3E5F5','#4A148C'],['#FFEBEE','#B71C1C'],['#E0F2F1','#00695C'],['#FFFDE7','#F57F17']];
export const ACCOUNTS={
  hq_admin:{pw:'hq1234',depot:'HQ',name:'HQ Administrator'},
  changamwe_officer:{pw:'cga123',depot:'Changamwe',name:'CGA Booking Officer'},
  mtito_officer:{pw:'mtt123',depot:'Mtito',name:'MTT Booking Officer'},
  makadara_officer:{pw:'mkd123',depot:'Makadara',name:'MKD Booking Officer'},
  nakuru_officer:{pw:'nkr123',depot:'Nakuru',name:'NKR Booking Officer'},
  kisumu_officer:{pw:'ksm123',depot:'Kisumu',name:'KSM Booking Officer'},
  eldoret_officer:{pw:'eld123',depot:'Eldoret',name:'ELD Booking Officer'},
};
export const SEED_CREW={
  Changamwe:[{id:'CG-001',name:'James Kamau Njoroge',grade:'Locomotive driver',route:'CGAâ€“MTT'},{id:'CG-002',name:'Mary Wanjiku Mwangi',grade:'Locomotive driver',route:'CGAâ€“NBI'},{id:'CG-003',name:'Peter Ochieng Otieno',grade:'Shunter',route:'CGA Yard'},{id:'CG-004',name:'Grace Akinyi Odhiambo',grade:'Guard',route:'CGAâ€“MTT'},{id:'CG-005',name:'Brian Otieno Ouma',grade:'Technician',route:'Workshop'},{id:'CG-006',name:'Christine Atieno Auma',grade:'Station Master',route:'CGA Station'}],
  Mtito:[{id:'MT-001',name:'Samuel Mwangi Gitau',grade:'Locomotive driver',route:'MTTâ€“NBI'},{id:'MT-002',name:'Alice Chebet Koech',grade:'Guard',route:'MTTâ€“CGA'},{id:'MT-003',name:'Daniel Kipchoge Ruto',grade:'Locomotive driver',route:'MTTâ€“NKR'},{id:'MT-004',name:'Lydia Wambui Kariuki',grade:'Station Master',route:'MTT Station'},{id:'MT-005',name:'Fredrick Mutua Kioko',grade:'Shunter',route:'MTT Yard'}],
  Makadara:[{id:'MK-001',name:'Esther Muthoni Kariuki',grade:'Station Master',route:'MKD Station'},{id:'MK-002',name:'John Mwangi Njiru',grade:'Locomotive driver',route:'MKDâ€“CGA'},{id:'MK-003',name:'Fatuma Hassan Abdi',grade:'Guard',route:'MKDâ€“MTT'},{id:'MK-004',name:'Kevin Otieno Omondi',grade:'Shunter',route:'MKD Yard'},{id:'MK-005',name:'Rose Njeri Kamau',grade:'Locomotive driver',route:'MKDâ€“NBI'},{id:'MK-006',name:'Charles Kimani Mwangi',grade:'Technician',route:'Workshop'}],
  Nakuru:[{id:'NK-001',name:'Paul Kimani Waweru',grade:'Locomotive driver',route:'NKRâ€“NBI'},{id:'NK-002',name:'Jane Muthoni Kariuki',grade:'Guard',route:'NKRâ€“ELD'},{id:'NK-003',name:'Moses Otieno Owino',grade:'Locomotive driver',route:'NKRâ€“KSM'},{id:'NK-004',name:"Catherine Wanjiru Ng'ang'a",grade:'Station Master',route:'NKR Station'},{id:'NK-005',name:'Elijah Koech Kipkirui',grade:'Shunter',route:'NKR Yard'}],
  Kisumu:[{id:'KS-001',name:'George Ouma Oketch',grade:'Locomotive driver',route:'KSMâ€“NKR'},{id:'KS-002',name:'Agnes Achieng Otieno',grade:'Guard',route:'KSMâ€“ELD'},{id:'KS-003',name:'David Odhiambo Onyango',grade:'Locomotive driver',route:'KSMâ€“NBI'},{id:'KS-004',name:'Mercy Adhiambo Ochieng',grade:'Station Master',route:'KSM Station'},{id:'KS-005',name:'Isaac Ogutu Were',grade:'Technician',route:'Workshop'},{id:'KS-006',name:'Beatrice Awuor Oloo',grade:'Shunter',route:'KSM Yard'}],
  Eldoret:[{id:'EL-001',name:'Joseph Kipkoech Ngetich',grade:'Locomotive driver',route:'ELDâ€“NKR'},{id:'EL-002',name:'Winnie Jepkosgei Kogo',grade:'Guard',route:'ELDâ€“KSM'},{id:'EL-003',name:'Robert Kibet Chirchir',grade:'Locomotive driver',route:'ELDâ€“NBI'},{id:'EL-004',name:'Esther Chelangat Bett',grade:'Station Master',route:'ELD Station'},{id:'EL-005',name:'Leonard Kipyego Biwott',grade:'Shunter',route:'ELD Yard'}],
};
export const TODAY=new Date();
export const CY=TODAY.getFullYear();
export const CM=TODAY.getMonth();
export const CD=TODAY.getDate();
export const DAYS_IN_MON=new Date(CY,CM+1,0).getDate();
export const MONTH_NAME=TODAY.toLocaleString('en-KE',{month:'long',year:'numeric'});
export const DAY_NAMES=['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
export const MONTH_KEY=`${CY}-${String(CM+1).padStart(2,'0')}`;
