// ===== QURBA: account sync engine (local stores <-> /api/v1/sync) =====
import { watch } from 'vue';
import { i18n } from './i18n';
import { bookmarks, bookmarkMeta, readerSettings, getLastRead, setLastRead } from './quranLocal';
import { audioPrefs } from './quranAudio';
import { tb } from './tasbeeh';
import { prayerSettings } from './prayer';
import { loc } from './location';
import { consent } from './consent';
import { ak, adhkarUpdatedAt } from './adhkarLocal';

const get = (k: string) => { try { return localStorage.getItem(k); } catch { return null; } };
const put = (k: string, v: string) => { try { localStorage.setItem(k, v); } catch {} };

export function deviceUuid() {
  let id = get('qurba.device');
  if (!id) { id = crypto.randomUUID ? crypto.randomUUID() : String(Date.now()) + Math.random().toString(16).slice(2); put('qurba.device', id); }
  return id;
}

// One timestamp for "preferences last changed on this device"
let applying = false;
const touchPrefs = () => { if (!applying) { put('qurba.prefsAt', String(Date.now())); put('qurba.syncDirty', '1'); } };
watch([readerSettings, audioPrefs, prayerSettings, () => loc.place, () => i18n.global.locale.value,
  () => [tb.sound, tb.vibrate, tb.dailyGoal, JSON.stringify(tb.targets)]], touchPrefs, { deep: true });

function xsrf() { const m = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/); return m ? decodeURIComponent(m[1]) : ''; }

export const currentUser = () => (window as any).__qurbaUser ?? null;
export const canSync = () => !!currentUser() && consent.items.cloud_backup;

function payload() {
  const standalone = matchMedia('(display-mode: standalone)').matches || (navigator as any).standalone === true;
  return {
    device: { uuid: deviceUuid(), name: navigator.userAgent.slice(0, 120), platform: standalone ? 'pwa' : 'web', version: '1' },
    consents: { items: consent.items, t: consent.t, version: '1' },
    bookmarks: Object.entries(bookmarkMeta).map(([key, m]) => ({ key, on: m.on, t: m.t })),
    lastRead: getLastRead(),
    prefs: {
      t: Number(get('qurba.prefsAt') || 0), language: i18n.global.locale.value,
      reader: { ...readerSettings }, audio: { ...audioPrefs },
      tasbeeh: { sound: tb.sound, vibrate: tb.vibrate, dailyGoal: tb.dailyGoal, targets: tb.targets },
      place: loc.place, prayer: { ...prayerSettings },
    },
    tasbeeh: { sessions: tb.history, custom: tb.custom, removed: tb.removed ?? [], daily: tb.daily },
    adhkar: { t: adhkarUpdatedAt(), progress: ak.progress, favorites: ak.favorites, translit: ak.translit },
    reminders: { t: Number(get('qurba.remindersAt') || 0), settings: (() => { try { return JSON.parse(get('qurba.reminders') || '{}'); } catch { return {}; } })() },
    downloads: { t: Number(get('qurba.downloadsAt') || 0), items: (() => { try { return JSON.parse(get('qurba.downloads') || '[]'); } catch { return []; } })() },
    listening: { t: Number(get('qurba.listeningAt') || 0), items: (() => { try { return JSON.parse(get('qurba.listening') || '[]'); } catch { return []; } })() },
  };
}

function apply(s: any) {
  applying = true;
  try {
    // bookmarks
    for (const k of Object.keys(bookmarkMeta)) delete bookmarkMeta[k];
    bookmarks.clear();
    for (const b of s.bookmarks ?? []) { bookmarkMeta[b.key] = { on: b.on, t: b.t }; if (b.on) bookmarks.add(b.key); }
    put('qurba.bookmarks', JSON.stringify({ keys: [...bookmarks] })); put('qurba.bookmarksMeta', JSON.stringify(bookmarkMeta));

    // last read
    const lr = getLastRead();
    if (s.lastRead && (!lr || s.lastRead.at > lr.at)) setLastRead(s.lastRead);

    // preferences (server newer wins)
    const p = s.prefs;
    if (p && p.t > Number(get('qurba.prefsAt') || 0)) {
      if (p.reader) Object.assign(readerSettings, p.reader);
      if (p.audio) Object.assign(audioPrefs, p.audio);
      if (p.tasbeeh) { tb.sound = p.tasbeeh.sound ?? tb.sound; tb.vibrate = p.tasbeeh.vibrate ?? tb.vibrate; tb.dailyGoal = p.tasbeeh.dailyGoal ?? tb.dailyGoal; tb.targets = p.tasbeeh.targets ?? tb.targets; }
      if (p.place) loc.place = p.place;
      if (p.prayer) { prayerSettings.method = p.prayer.method ?? ''; prayerSettings.asr = p.prayer.asr ?? ''; if (p.prayer.adjust) prayerSettings.adjust = p.prayer.adjust; }
      if (p.language && ['en', 'ar', 'ur'].includes(p.language)) i18n.global.locale.value = p.language;
      put('qurba.prefsAt', String(p.t));
    }

    // adhkar
    if (s.adhkar && s.adhkar.t >= adhkarUpdatedAt()) {
      ak.progress = s.adhkar.progress ?? ak.progress; ak.favorites = s.adhkar.favorites ?? ak.favorites; ak.translit = s.adhkar.translit ?? ak.translit;
      put('qurba.adhkarAt', String(s.adhkar.t || Date.now()));
    }
    if (s.reminders?.settings && s.reminders.t >= Number(get('qurba.remindersAt') || 0)) { put('qurba.reminders', JSON.stringify(s.reminders.settings)); put('qurba.remindersAt', String(s.reminders.t)); }
    if (s.downloads?.items && s.downloads.t >= Number(get('qurba.downloadsAt') || 0)) { put('qurba.downloads', JSON.stringify(s.downloads.items)); put('qurba.downloadsAt', String(s.downloads.t)); }
    if (s.listening?.items && s.listening.t >= Number(get('qurba.listeningAt') || 0)) { put('qurba.listening', JSON.stringify(s.listening.items)); put('qurba.listeningAt', String(s.listening.t)); }

    // tasbeeh
    const t = s.tasbeeh ?? {};
    tb.custom = (t.custom ?? []).map((c: any) => ({ id: c.id, ar: c.ar, label: { en: c.ar, ar: c.ar, ur: c.ar }, target: c.target, builtin: false }));
    tb.removed = [];
    if (tb.custom.every((c) => c.id !== tb.activeId) && tb.activeId.startsWith('c-')) tb.activeId = 'subhanallah';
    tb.history = (t.sessions ?? []).filter((x: any) => x.typeId);
    for (const [day, types] of Object.entries<Record<string, number>>(t.daily ?? {})) {
      const d = (tb.daily[day] ??= {});
      for (const [id, n] of Object.entries(types)) if (id && n > (d[id] ?? 0)) d[id] = n;
    }

    // consents from server only if this device has never decided
    if (!consent.decided && s.consents) { Object.assign(consent.items, s.consents); consent.decided = true; }
  } finally { setTimeout(() => (applying = false)); }
}

export let lastDevices: any[] = [];

export async function userSync(): Promise<boolean> {
  if (!canSync()) return false;
  const r = await fetch('/api/v1/sync', {
    method: 'POST', credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': xsrf(), 'X-Requested-With': 'XMLHttpRequest' },
    body: JSON.stringify(payload()),
  });
  if (!r.ok) return false;
  const j = await r.json();
  apply(j.data);
  lastDevices = j.data.devices ?? [];
  put('qurba.syncDirty', '0');
  return true;
}
