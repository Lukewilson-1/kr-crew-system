export const DEPOTS=['Changamwe','Mtito','Makadara','Nakuru','Kisumu','Eldoret'];
export const DEPOT_COLORS={Changamwe:'#1B5E20',Mtito:'#0D47A1',Makadara:'#4A148C',Nakuru:'#B71C1C',Kisumu:'#E65100',Eldoret:'#37474F'};
export const REST_HOURS={Changamwe:12,Makadara:12,Kisumu:12,Eldoret:12,Mtito:10,Nakuru:10,Malaba:10,Sagana:10};
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
export const DRIVER_GRADES=['Driver A','Driver B'];
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
  Changamwe:[{id:'CG-001',name:'James Kamau Njoroge',grade:'Driver A',route:'CGAâ€“MTT'},{id:'CG-002',name:'Mary Wanjiku Mwangi',grade:'Driver B',route:'CGAâ€“NBI'},{id:'CG-003',name:'Peter Ochieng Otieno',grade:'Shunter',route:'CGA Yard'},{id:'CG-004',name:'Grace Akinyi Odhiambo',grade:'Guard',route:'CGAâ€“MTT'},{id:'CG-005',name:'Brian Otieno Ouma',grade:'Technician',route:'Workshop'},{id:'CG-006',name:'Christine Atieno Auma',grade:'Station Master',route:'CGA Station'}],
  Mtito:[{id:'MT-001',name:'Samuel Mwangi Gitau',grade:'Driver A',route:'MTTâ€“NBI'},{id:'MT-002',name:'Alice Chebet Koech',grade:'Guard',route:'MTTâ€“CGA'},{id:'MT-003',name:'Daniel Kipchoge Ruto',grade:'Driver B',route:'MTTâ€“NKR'},{id:'MT-004',name:'Lydia Wambui Kariuki',grade:'Station Master',route:'MTT Station'},{id:'MT-005',name:'Fredrick Mutua Kioko',grade:'Shunter',route:'MTT Yard'}],
  Makadara:[{id:'MK-001',name:'Esther Muthoni Kariuki',grade:'Station Master',route:'MKD Station'},{id:'MK-002',name:'John Mwangi Njiru',grade:'Driver A',route:'MKDâ€“CGA'},{id:'MK-003',name:'Fatuma Hassan Abdi',grade:'Guard',route:'MKDâ€“MTT'},{id:'MK-004',name:'Kevin Otieno Omondi',grade:'Shunter',route:'MKD Yard'},{id:'MK-005',name:'Rose Njeri Kamau',grade:'Driver B',route:'MKDâ€“NBI'},{id:'MK-006',name:'Charles Kimani Mwangi',grade:'Technician',route:'Workshop'}],
  Nakuru:[{id:'NK-001',name:'Paul Kimani Waweru',grade:'Driver A',route:'NKRâ€“NBI'},{id:'NK-002',name:'Jane Muthoni Kariuki',grade:'Guard',route:'NKRâ€“ELD'},{id:'NK-003',name:'Moses Otieno Owino',grade:'Driver B',route:'NKRâ€“KSM'},{id:'NK-004',name:"Catherine Wanjiru Ng'ang'a",grade:'Station Master',route:'NKR Station'},{id:'NK-005',name:'Elijah Koech Kipkirui',grade:'Shunter',route:'NKR Yard'}],
  Kisumu:[{id:'KS-001',name:'George Ouma Oketch',grade:'Driver A',route:'KSMâ€“NKR'},{id:'KS-002',name:'Agnes Achieng Otieno',grade:'Guard',route:'KSMâ€“ELD'},{id:'KS-003',name:'David Odhiambo Onyango',grade:'Driver B',route:'KSMâ€“NBI'},{id:'KS-004',name:'Mercy Adhiambo Ochieng',grade:'Station Master',route:'KSM Station'},{id:'KS-005',name:'Isaac Ogutu Were',grade:'Technician',route:'Workshop'},{id:'KS-006',name:'Beatrice Awuor Oloo',grade:'Shunter',route:'KSM Yard'}],
  Eldoret:[{id:'EL-001',name:'Joseph Kipkoech Ngetich',grade:'Driver A',route:'ELDâ€“NKR'},{id:'EL-002',name:'Winnie Jepkosgei Kogo',grade:'Guard',route:'ELDâ€“KSM'},{id:'EL-003',name:'Robert Kibet Chirchir',grade:'Driver B',route:'ELDâ€“NBI'},{id:'EL-004',name:'Esther Chelangat Bett',grade:'Station Master',route:'ELD Station'},{id:'EL-005',name:'Leonard Kipyego Biwott',grade:'Shunter',route:'ELD Yard'}],
};
export const TODAY=new Date();
export const CY=TODAY.getFullYear();
export const CM=TODAY.getMonth();
export const CD=TODAY.getDate();
export const DAYS_IN_MON=new Date(CY,CM+1,0).getDate();
export const MONTH_NAME=TODAY.toLocaleString('en-KE',{month:'long',year:'numeric'});
export const DAY_NAMES=['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
export const MONTH_KEY=`${CY}-${String(CM+1).padStart(2,'0')}`;
