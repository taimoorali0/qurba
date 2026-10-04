// ===== QURBA: prayer times (adhan-js, calculated on the device) =====
import { reactive, watch } from 'vue';
import { CalculationMethod, Coordinates, HighLatitudeRule, Madhab, PrayerTimes } from 'adhan';
import type { Place } from './location';

export const PRAYERS = ['fajr', 'sunrise', 'dhuhr', 'asr', 'maghrib', 'isha'] as const;
export type PrayerName = typeof PRAYERS[number];

export const METHODS = {
  Karachi: 'University of Islamic Sciences, Karachi',
  UmmAlQura: 'Umm al-Qura, Makkah',
  MuslimWorldLeague: 'Muslim World League',
  Egyptian: 'Egyptian General Authority',
  Dubai: 'Dubai',
  Kuwait: 'Kuwait',
  Qatar: 'Qatar',
  MoonsightingCommittee: 'Moonsighting Committee',
  NorthAmerica: 'ISNA (North America)',
  Singapore: 'Singapore',
  Turkey: 'Diyanet (Turkey)',
  Tehran: 'Tehran',
} as const;
export type MethodKey = keyof typeof METHODS;

const COUNTRY_DEFAULTS: Record<string, { method: MethodKey; asr: 'standard' | 'hanafi' }> = {
  PK: { method: 'Karachi', asr: 'hanafi' }, IN: { method: 'Karachi', asr: 'hanafi' }, BD: { method: 'Karachi', asr: 'hanafi' },
  SA: { method: 'UmmAlQura', asr: 'standard' }, AE: { method: 'Dubai', asr: 'standard' }, KW: { method: 'Kuwait', asr: 'standard' },
  QA: { method: 'Qatar', asr: 'standard' }, EG: { method: 'Egyptian', asr: 'standard' }, TR: { method: 'Turkey', asr: 'standard' },
  GB: { method: 'MoonsightingCommittee', asr: 'standard' }, US: { method: 'NorthAmerica', asr: 'standard' }, CA: { method: 'NorthAmerica', asr: 'standard' },
};

interface Settings { method: MethodKey | ''; asr: 'standard' | 'hanafi' | ''; adjust: Record<PrayerName, number> }
const zero = () => ({ fajr: 0, sunrise: 0, dhuhr: 0, asr: 0, maghrib: 0, isha: 0 });
function load(): Settings { try { const v = localStorage.getItem('qurba.prayer'); return v ? { method: '', asr: '', adjust: zero(), ...JSON.parse(v) } : { method: '', asr: '', adjust: zero() }; } catch { return { method: '', asr: '', adjust: zero() }; } }
export const prayerSettings = reactive<Settings>(load());
watch(prayerSettings, (v) => { try { localStorage.setItem('qurba.prayer', JSON.stringify(v)); } catch {} }, { deep: true });

/** Method/asr actually used: user's choice, else the country default, else Muslim World League. */
export function effective(place: Place) {
  const d = COUNTRY_DEFAULTS[place.country] ?? { method: 'MuslimWorldLeague' as MethodKey, asr: 'standard' as const };
  return { method: (prayerSettings.method || d.method) as MethodKey, asr: prayerSettings.asr || d.asr, isDefault: !prayerSettings.method };
}

export function timesFor(place: Place, date: Date) {
  const { method, asr } = effective(place);
  const params = (CalculationMethod as any)[method]();
  params.madhab = asr === 'hanafi' ? Madhab.Hanafi : Madhab.Shafi;
  if (Math.abs(place.lat) > 48) params.highLatitudeRule = HighLatitudeRule.recommended(new Coordinates(place.lat, place.lng));
  params.adjustments = { ...prayerSettings.adjust };
  const pt = new PrayerTimes(new Coordinates(place.lat, place.lng), date, params);
  return PRAYERS.map((name) => ({ name, time: (pt as any)[name] as Date, adjusted: prayerSettings.adjust[name] !== 0 }));
}

/** Next prayer from now (skips sunrise), rolling into tomorrow's Fajr after Isha. */
export function nextPrayer(place: Place, now = new Date()) {
  const list = timesFor(place, now).filter((p) => p.name !== 'sunrise');
  const n = list.find((p) => p.time > now);
  if (n) return n;
  const tmr = new Date(now); tmr.setDate(tmr.getDate() + 1);
  return timesFor(place, tmr)[0];
}

export const fmtTime = (d: Date, tz: string, locale: string) =>
  d.toLocaleTimeString(locale, { hour: 'numeric', minute: '2-digit', timeZone: tz });

export function countdown(to: Date, now = new Date()) {
  const s = Math.max(0, Math.floor((to.getTime() - now.getTime()) / 1000));
  const h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60);
  return h ? `${h}h ${m}m` : `${m}m`;
}
