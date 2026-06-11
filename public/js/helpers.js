import { REST_HOURS, DRIVER_GRADES, STATUS_META } from './constants.js';

export function getRestHours(depotOrCrew){
  if(!depotOrCrew) return 12;
  if(typeof depotOrCrew === 'object'){
    const crew = depotOrCrew;
    return crew.awayDepot && crew.awayDepot !== crew.depot ? 10 : 12;
  }
  return REST_HOURS[depotOrCrew]||12;
}
function parseTimestamp(value){
  if(!value) return null;
  if(typeof value === 'string' || value instanceof String){
    const d = new Date(value);
    return isNaN(d.getTime()) ? null : d;
  }
  if(value.toDate) return value.toDate();
  if(typeof value.seconds === 'number' && typeof value.nanoseconds === 'number'){
    return new Date(value.seconds * 1000 + Math.floor(value.nanoseconds / 1000000));
  }
  return null;
}

export function restSecondsLeft(crew){
  if(crew.status!=='R'||!crew.restStarted) return null;
  const maxH=getRestHours(crew);
  const startDate = parseTimestamp(crew.restStarted);
  if(!startDate) return null;
  const started=startDate.getTime();
  const endsAt=started+maxH*3600*1000;
  return Math.max(0,Math.floor((endsAt-Date.now())/1000));
}
export function fmtCountdown(sec){
  if(sec<=0) return '00:00:00';
  const h=Math.floor(sec/3600),m=Math.floor((sec%3600)/60),s=sec%60;
  return `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
}
export function cdClass(sec,maxH){
  const pct=sec/(maxH*3600);
  if(pct>0.5) return 'ok';
  if(pct>0.2) return 'warn';
  return 'crit';
}
export function getAllCrew(state, depots){return depots.flatMap(d=>Object.values(state[d]||{}));}
export function cts(arr){const c={};arr.forEach(x=>{c[x.status]=(c[x.status]||0)+1;});return c;}
export function initials(n){const p=n.trim().split(/\s+/);return (p[0][0]+(p[p.length-1][0]||'')).toUpperCase();}
export function fmtTime(d){
  const dt = d && d.toDate ? d.toDate() : new Date(d);
  if(isNaN(dt.getTime())) return '--';
  return dt.toLocaleTimeString('en-KE',{hour:'2-digit',minute:'2-digit'});
}
export function todayStr(){return new Date().toISOString().split('T')[0];}
export function fmtLastUpd(ts){
  if(!ts) return '--';
  const d=ts.toDate?ts.toDate():new Date(ts);
  if(isNaN(d.getTime())) return '--';
  const diff=Math.floor((Date.now()-d.getTime())/1000);
  if(diff<60) return diff+'s ago';
  if(diff<3600) return Math.floor(diff/60)+'m ago';
  return fmtTime(d);
}
export function kpiHtml(items,cols='repeat(auto-fit,minmax(88px,1fr))'){
  return `<div class="kpi-row" style="grid-template-columns:${cols}">${items.map(([k,l,v])=>`<div class="kpi-card kc-${k}"><div class="kpi-n">${v}</div><div class="kpi-l">${l}</div></div>`).join('')}</div>`;
}
export function dlCSV(csv,fname){const b=new Blob([csv],{type:'text/csv'});const a=document.createElement('a');a.href=URL.createObjectURL(b);a.download=fname;document.body.appendChild(a);a.click();document.body.removeChild(a);}
