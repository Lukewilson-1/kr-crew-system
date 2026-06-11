export const DEPOTS=[];
export let DEPOT_COLORS={};
export let REST_HOURS={};
export const TRAIN_TYPES=['Freight','Commuter','Passenger','Engineering','Shunting'];
export let STATUS_META={};
export let STATUSES=[];
export const DRIVER_GRADES=['locomotive_driver'];
export const AVT_PAL=[['#E8F5E9','#1B5E20'],['#E3F2FD','#0D47A1'],['#FFF3E0','#E65100'],['#F3E5F5','#4A148C'],['#FFEBEE','#B71C1C'],['#E0F2F1','#00695C'],['#FFFDE7','#F57F17']];
export function setDepotConfig(depots, colors, hours){
  DEPOTS.length = 0;
  DEPOTS.push(...(Array.isArray(depots)?depots:[]));
  DEPOT_COLORS = colors || {};
  REST_HOURS = hours || {};
}
export function setStatusConfig(statuses, meta){
  STATUSES = Array.isArray(statuses)?statuses:[];
  STATUS_META = meta || {};
}
let designationRegistry={};
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
  designationRegistry=Object.keys(registry||{}).length?cloneDesignationRegistry(registry):{};
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
