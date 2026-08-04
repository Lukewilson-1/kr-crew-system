export async function init(){
  if(!window.__CREW_APP_READY) await new Promise(res=>{const i=setInterval(()=>{if(window.__CREW_APP_READY){clearInterval(i);res();}},40);});
  if(typeof window.renderDashboard === 'function'){
    window.renderDashboard();
    return;
  }
  // fallback: call refreshPage if render function missing
  if(typeof window.refreshPage === 'function') window.refreshPage();
}
