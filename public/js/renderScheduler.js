export function createRenderScheduler(callback){
  let scheduled = false;

  return function scheduleRender(){
    if(scheduled) return;
    scheduled = true;
    setTimeout(() => {
      scheduled = false;
      callback();
    }, 0);
  };
}
