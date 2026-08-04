export async function init(){
  if(!window.__CREW_APP_READY) await new Promise(res=>{const i=setInterval(()=>{if(window.__CREW_APP_READY){clearInterval(i);res();}},40);});
  if(typeof window.renderReports === 'function'){
    window.renderReports();
    return;
  }
  if(typeof window.refreshPage === 'function') window.refreshPage();
}
