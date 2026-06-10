import { initializeApp, getApps, deleteApp, getApp } from "https://www.gstatic.com/firebasejs/10.12.0/firebase-app.js";
import { getFirestore, enableIndexedDbPersistence } from "https://www.gstatic.com/firebasejs/10.12.0/firebase-firestore.js";

export let db=null;
export let demoMode=false;

export function setDemoMode(value){demoMode=value; if(value) db=null;}

export function initFirebase(cfg){
  try{
    if(getApps().length) deleteApp(getApp());
    initializeApp(cfg);
    db=getFirestore();
    enableIndexedDbPersistence(db).catch(() => {});
    return true;
  }catch(err){
    console.error('Firebase init failed', err);
    return false;
  }
}

export async function loadFirebaseConfig(){
  try{
    if(window.FIREBASE_CONFIG && window.FIREBASE_CONFIG.apiKey && window.FIREBASE_CONFIG.projectId){
      return window.FIREBASE_CONFIG;
    }
    const resp = await fetch('/.env', { cache: 'no-store' });
    if(!resp.ok) return null;
    const text = await resp.text();
    const config = {};

    for(const line of text.split(/\r?\n/)){
      const trimmed = line.trim();
      if(!trimmed || trimmed.startsWith('#')) continue;
      const [key, ...rest] = trimmed.split('=');
      if(!key || rest.length === 0) continue;
      const value = rest.join('=').trim();
      if(!value) continue;
      if(key === 'FIREBASE_API_KEY') config.apiKey = value;
      else if(key === 'FIREBASE_AUTH_DOMAIN') config.authDomain = value;
      else if(key === 'FIREBASE_PROJECT_ID') config.projectId = value;
      else if(key === 'FIREBASE_STORAGE_BUCKET') config.storageBucket = value;
      else if(key === 'FIREBASE_MESSAGING_SENDER_ID') config.messagingSenderId = value;
      else if(key === 'FIREBASE_APP_ID') config.appId = value;
    }

    return config.apiKey && config.projectId ? config : null;
  }catch(err){
    console.error('Error loading Firebase config', err);
    return null;
  }
}
