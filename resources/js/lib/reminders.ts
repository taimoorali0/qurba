// ===== QURBA: prayer + adhkar reminders (Web Push). Times come from the device's prayer calculation. =====
import { reactive, watch } from 'vue';
import { i18n } from './i18n';
import { loc } from './location';
import { timesFor } from './prayer';
import { deviceUuid } from './userSync';
import { consent, setConsent } from './consent';

const PRAYERS = ['fajr', 'dhuhr', 'asr', 'maghrib', 'isha'] as const;
interface Settings { enabled: boolean; prayers: Record<string, boolean>; offset: number;
  morning: { on: boolean; time: string }; evening: { on: boolean; time: string }; scheduledAt: number }
function load(): Settings {
  const d: Settings = { enabled: false, prayers: { fajr: true, dhuhr: true, asr: true, maghrib: true, isha: true }, offset: 0,
    morning: { on: false, time: '07:00' }, evening: { on: false, time: '17:30' }, scheduledAt: 0 };
  try { const v = localStorage.getItem('qurba.reminders'); return v ? { ...d, ...JSON.parse(v) } : d; } catch { return d; }
}
export const rem = reactive<Settings>(load());
watch(rem, (v) => { try { localStorage.setItem('qurba.reminders', JSON.stringify(v)); localStorage.setItem('qurba.remindersAt', String(Date.now())); localStorage.setItem('qurba.syncDirty', '1'); } catch {} }, { deep: true });

export type Support = 'ok' | 'unsupported' | 'ios-install' | 'needs-build' | 'denied';
export function support(): Support {
  const standalone = matchMedia('(display-mode: standalone)').matches || (navigator as any).standalone === true;
  if (/iphone|ipad|ipod/i.test(navigator.userAgent) && !standalone) return 'ios-install';
  if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) return 'unsupported';
  if (Notification.permission === 'denied') return 'denied';
  if (!navigator.serviceWorker.controller) return 'needs-build';
  return 'ok';
}

function xsrf() { const m = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/); return m ? decodeURIComponent(m[1]) : ''; }
const post = (url: string, body: unknown) => fetch(url, { method: 'POST', credentials: 'same-origin',
  headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': xsrf(), 'X-Requested-With': 'XMLHttpRequest' },
  body: JSON.stringify(body) });

function keyBytes(b64: string) {
  const pad = '='.repeat((4 - (b64.length % 4)) % 4);
  const raw = atob((b64 + pad).replace(/-/g, '+').replace(/_/g, '/'));
  return Uint8Array.from(raw, (c) => c.charCodeAt(0));
}

/** Must be called from a button tap. Returns an error code or '' on success. */
export async function enableReminders(): Promise<string> {
  const s = support(); if (s !== 'ok') return s;
  if ((await Notification.requestPermission()) !== 'granted') return 'denied';
  const { key } = await (await fetch('/api/v1/push/key')).json();
  if (!key) return 'server';
  const reg = await navigator.serviceWorker.ready;
  const sub = (await reg.pushManager.getSubscription()) ?? await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: keyBytes(key) });
  const r = await post('/api/v1/push/subscribe', { device: deviceUuid(), subscription: sub.toJSON(), locale: i18n.global.locale.value });
  if (!r.ok) return 'server';
  rem.enabled = true; setConsent('notifications', true);
  await scheduleReminders(true);
  return '';
}

export async function disableReminders() {
  rem.enabled = false; setConsent('notifications', false);
  try { const reg = await navigator.serviceWorker.ready; await (await reg.pushManager.getSubscription())?.unsubscribe(); } catch {}
  await post('/api/v1/push/unsubscribe', { device: deviceUuid() }).catch(() => {});
}

function at(day: Date, hhmm: string) { const [h, m] = hhmm.split(':').map(Number); const d = new Date(day); d.setHours(h, m, 0, 0); return d; }

/** Sends the next 7 days of reminders. Runs on app start (once a day) and after every settings change. */
export async function scheduleReminders(force = false) {
  if (!rem.enabled || !consent.items.notifications || typeof Notification === 'undefined' || Notification.permission !== 'granted') return;
  if (!force && Date.now() - rem.scheduledAt < 12 * 3600e3) return;
  const t = i18n.global.t; const now = Date.now(); const items: any[] = [];
  for (let i = 0; i < 7; i++) {
    const day = new Date(); day.setDate(day.getDate() + i);
    if (loc.place) for (const p of timesFor(loc.place, day)) {
      if (!(PRAYERS as readonly string[]).includes(p.name) || !rem.prayers[p.name]) continue;
      const fire = new Date(p.time.getTime() - rem.offset * 60000);
      if (fire.getTime() > now) items.push({ kind: p.name, fire_at: fire.toISOString(), url: '/prayer',
        title: t('reminders.prayerTitle', { p: t('prayer.' + p.name) }),
        body: rem.offset ? t('reminders.prayerSoon', { n: rem.offset }) : t('reminders.prayerNow') });
    }
    for (const [kind, s, slug] of [['morning_adhkar', rem.morning, 'morning'], ['evening_adhkar', rem.evening, 'evening']] as const) {
      const fire = at(day, s.time);
      if (s.on && fire.getTime() > now) items.push({ kind, fire_at: fire.toISOString(), url: `/zikr/adhkar/${slug}`,
        title: t(`reminders.${slug}Title`), body: t('reminders.adhkarBody') });
    }
  }
  const r = await post('/api/v1/push/schedule', { device: deviceUuid(), items });
  if (r.ok) rem.scheduledAt = Date.now();
}
