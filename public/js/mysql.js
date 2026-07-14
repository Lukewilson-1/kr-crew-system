const ADMIN_META_ENDPOINT = '/admin/meta';
const DIRECT_META_ENDPOINT = '/mysql/meta';
const DIRECT_USERS_ENDPOINT = '/mysql/users';
const CREW_ENDPOINT = '/mysql/crew-view';
const DIRECT_META_COLLECTIONS = new Set(['depotMeta', 'statusMeta', 'trainTypeMeta', 'shiftMeta', 'users', 'roles', 'permissions']);

export const db = { provider: 'mysql' };
export let demoMode = false;

export function setDemoMode(value) {
  demoMode = false;
  return value;
}

export async function loadBackendConfig() {
  try {
    const resp = await fetch(ADMIN_META_ENDPOINT, { cache: 'no-store' });
    if (!resp.ok) return null;
    return { provider: 'mysql' };
  } catch (err) {
    console.error('MySQL backend check failed', err);
    return null;
  }
}

export function initBackend() {
  return true;
}

function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function deepClone(value) {
  return value === undefined ? value : JSON.parse(JSON.stringify(value));
}

function normalizeWhere(whereClause) {
  if (!whereClause) return [];
  if (Array.isArray(whereClause)) return whereClause.filter(Boolean);
  return [whereClause];
}

function createDocSnapshot(id, data) {
  return {
    id,
    exists: () => true,
    data: () => deepClone(data),
  };
}

function createEmptyDocSnapshot() {
  return {
    exists: () => false,
    data: () => undefined,
  };
}

function createQuerySnapshot(rows) {
  const docs = rows.map(row => ({
    id: row.id,
    data: () => deepClone(row),
  }));
  return {
    empty: docs.length === 0,
    docs,
    forEach(callback) {
      docs.forEach(callback);
    },
    docChanges() {
      return docs.map(doc => ({ type: 'added', doc }));
    },
  };
}

async function fetchAdminMeta() {
  const resp = await fetch(ADMIN_META_ENDPOINT, { cache: 'no-store' });
  if (!resp.ok) throw new Error(`Admin meta fetch failed (${resp.status})`);
  return resp.json();
}

async function fetchDirectMetaCollection(collectionName) {
  const endpoint = collectionName === 'users' ? DIRECT_USERS_ENDPOINT : `${DIRECT_META_ENDPOINT}/${encodeURIComponent(collectionName)}`;
  const resp = await fetch(endpoint, { cache: 'no-store' });
  if (!resp.ok) throw new Error(`Direct meta fetch failed (${resp.status})`);
  return resp.json();
}

async function fetchCrewRecords(depot = '') {
  const url = depot ? `${CREW_ENDPOINT}?depot=${encodeURIComponent(depot)}` : CREW_ENDPOINT;
  const resp = await fetch(url, { cache: 'no-store' });
  if (!resp.ok) throw new Error(`Crew fetch failed (${resp.status})`);
  const data = await resp.json();
  return Array.isArray(data) ? data : (data.crew || []);
}

function isCrewCollection(source) {
  return source?.name === 'crew';
}

function isMetaCollection(source) {
  return source?.name && source.name !== 'crew';
}

async function loadCollection(source) {
  if (isCrewCollection(source)) {
    const depotFilter = normalizeWhere(source.filters).find(item => item.field === 'depot' && item.op === '==')?.value || '';
    return fetchCrewRecords(depotFilter);
  }

  if (isMetaCollection(source)) {
    if (DIRECT_META_COLLECTIONS.has(source.name)) {
      return fetchDirectMetaCollection(source.name);
    }

    const data = await fetchAdminMeta();
    return Array.isArray(data[source.name]) ? data[source.name] : [];
  }

  return [];
}

export function collection(_db, name) {
  return { name };
}

export function where(field, op, value) {
  return { field, op, value };
}

export function query(source, ...filters) {
  return { name: source?.name, filters: filters.flat().filter(Boolean) };
}

export function doc(_db, collectionName, id) {
  return { collectionName, id };
}

export async function getDocs(source) {
  const rows = await loadCollection(source);
  return createQuerySnapshot(rows);
}

export async function getDoc(docRef) {
  if (docRef.collectionName === 'crew') {
    const rows = await fetchCrewRecords();
    const row = rows.find(item => String(item.id) === String(docRef.id));
    return row ? createDocSnapshot(docRef.id, row) : createEmptyDocSnapshot();
  }

  if (DIRECT_META_COLLECTIONS.has(docRef.collectionName)) {
    const rows = await fetchDirectMetaCollection(docRef.collectionName);
    const row = Array.isArray(rows) ? rows.find(item => String(item.id) === String(docRef.id)) : null;
    return row ? createDocSnapshot(docRef.id, row) : createEmptyDocSnapshot();
  }

  const data = await fetchAdminMeta();
  const row = (data[docRef.collectionName] || []).find(item => String(item.id) === String(docRef.id));
  return row ? createDocSnapshot(docRef.id, row) : createEmptyDocSnapshot();
}

async function saveDirectMetaRecord(collectionName, id, payload) {
  const endpoint = collectionName === 'users' ? `${DIRECT_USERS_ENDPOINT}/${encodeURIComponent(id)}` : `${DIRECT_META_ENDPOINT}/${encodeURIComponent(collectionName)}/${encodeURIComponent(id)}`;
  const resp = await fetch(endpoint, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': getCsrfToken(),
    },
    body: JSON.stringify(payload),
  });
  if (!resp.ok) {
    throw new Error(await resp.text());
  }
  return resp.json();
}

async function deleteDirectMetaRecord(collectionName, id) {
  const endpoint = collectionName === 'users' ? `${DIRECT_USERS_ENDPOINT}/${encodeURIComponent(id)}` : `${DIRECT_META_ENDPOINT}/${encodeURIComponent(collectionName)}/${encodeURIComponent(id)}`;
  const resp = await fetch(endpoint, {
    method: 'DELETE',
    headers: {
      'X-CSRF-TOKEN': getCsrfToken(),
    },
  });
  if (!resp.ok) {
    throw new Error(await resp.text());
  }
  return resp.json();
}

async function saveMetaRecord(collectionName, id, payload) {
  const resp = await fetch(`${ADMIN_META_ENDPOINT}/${encodeURIComponent(collectionName)}/${encodeURIComponent(id)}`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': getCsrfToken(),
    },
    body: JSON.stringify(payload),
  });
  if (!resp.ok) {
    throw new Error(await resp.text());
  }
  return resp.json();
}

async function deleteMetaRecord(collectionName, id) {
  const resp = await fetch(`${ADMIN_META_ENDPOINT}/${encodeURIComponent(collectionName)}/${encodeURIComponent(id)}`, {
    method: 'DELETE',
    headers: {
      'X-CSRF-TOKEN': getCsrfToken(),
    },
  });
  if (!resp.ok) {
    throw new Error(await resp.text());
  }
  return resp.json();
}

async function saveCrewRecord(id, payload) {
  const resp = await fetch(`${CREW_ENDPOINT}/${encodeURIComponent(id)}`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': getCsrfToken(),
    },
    body: JSON.stringify(payload),
  });
  if (!resp.ok) {
    throw new Error(await resp.text());
  }
  return resp.json();
}

async function deleteCrewRecord(id) {
  const resp = await fetch(`${CREW_ENDPOINT}/${encodeURIComponent(id)}`, {
    method: 'DELETE',
    headers: {
      'X-CSRF-TOKEN': getCsrfToken(),
    },
  });
  if (!resp.ok) {
    throw new Error(await resp.text());
  }
  return resp.json();
}

export async function setDoc(docRef, payload, options = {}) {
  let next = deepClone(payload) || {};
  if (next.lastUpdated && typeof next.lastUpdated === 'object') {
    next.lastUpdated = new Date().toISOString();
  }

  if (docRef.collectionName === 'crew') {
    return saveCrewRecord(docRef.id, next);
  }

  if (DIRECT_META_COLLECTIONS.has(docRef.collectionName)) {
    if (options?.merge) {
      const current = await getDoc(docRef);
      if (current.exists()) {
        next = { ...current.data(), ...next };
      }
    }

    return saveDirectMetaRecord(docRef.collectionName, docRef.id, next);
  }

  if (options?.merge) {
    const current = await getDoc(docRef);
    if (current.exists()) {
      next = { ...current.data(), ...next };
    }
  }

  return saveMetaRecord(docRef.collectionName, docRef.id, next);
}

export async function deleteDoc(docRef) {
  if (docRef.collectionName === 'crew') {
    return deleteCrewRecord(docRef.id);
  }

  if (DIRECT_META_COLLECTIONS.has(docRef.collectionName)) {
    return deleteDirectMetaRecord(docRef.collectionName, docRef.id);
  }

  return deleteMetaRecord(docRef.collectionName, docRef.id);
}

export function writeBatch() {
  const ops = [];
  return {
    set(docRef, payload, options) {
      ops.push(() => setDoc(docRef, payload, options));
    },
    delete(docRef) {
      ops.push(() => deleteDoc(docRef));
    },
    async commit() {
      for (const op of ops) {
        await op();
      }
    },
  };
}

export function serverTimestamp() {
  return new Date().toISOString();
}

export function onSnapshot(source, callback, errorCallback) {
  let active = true;
  let previous = new Map();

  const poll = async () => {
    if (!active) return;
    try {
      const rows = await loadCollection(source);
      const next = new Map(rows.map(row => [String(row.id), row]));
      const changes = [];

      for (const [id, row] of next.entries()) {
        const prev = previous.get(id);
        if (!prev) {
          changes.push({ type: 'added', doc: { id, data: () => deepClone(row) } });
        } else if (JSON.stringify(prev) !== JSON.stringify(row)) {
          changes.push({ type: 'modified', doc: { id, data: () => deepClone(row) } });
        }
      }

      for (const [id, row] of previous.entries()) {
        if (!next.has(id)) {
          changes.push({ type: 'removed', doc: { id, data: () => deepClone(row) } });
        }
      }

      previous = next;
      callback({
        docChanges: () => changes,
      });
    } catch (err) {
      if (errorCallback) errorCallback(err);
    }
  };

  poll();
  const timer = setInterval(poll, 30000); // Poll every 30 seconds instead of 10 to reduce disruptions

  return () => {
    active = false;
    clearInterval(timer);
  };
}
