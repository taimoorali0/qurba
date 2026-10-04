// ===== QURBA: Salah tracker (which fard prayers were prayed, per day), on this device =====
import { reactive, watch } from 'vue';

export const FARD = ['fajr', 'dhuhr', 'asr', 'maghrib', 'isha'] as const;
export type Fard = typeof FARD[number];
type Day = Partial<Record<Fard, boolean>>;

const KEY = 'qurba.salah';
const KEEP_DAYS = 400;
const dayKey = (d: Date) => d.toLocaleDateString('en-CA'); // YYYY-MM-DD local

function load(): Record<string, Day> {
  try { return JSON.parse(localStorage.getItem(KEY) ?? '{}') ?? {}; } catch { return {}; }
}
export const salah = reactive<Record<string, Day>>(load());
watch(salah, (v) => {
  const keys = Object.keys(v).sort();
  if (keys.length > KEEP_DAYS) for (const k of keys.slice(0, keys.length - KEEP_DAYS)) delete v[k];
  try { localStorage.setItem(KEY, JSON.stringify(v)); } catch {}
}, { deep: true });

export const isPrayed = (d: Date, p: Fard) => !!salah[dayKey(d)]?.[p];
export function togglePrayed(d: Date, p: Fard) {
  const k = dayKey(d);
  const day = (salah[k] ??= {});
  if (day[p]) delete day[p]; else day[p] = true;
  if (!Object.keys(day).length) delete salah[k];
}
export const prayedCount = (d: Date) => FARD.filter((p) => isPrayed(d, p)).length;

/** Days in a row with all five prayers, counting back from today (today counts only once complete). */
export function streak(now = new Date()) {
  const d = new Date(now);
  if (prayedCount(d) < 5) d.setDate(d.getDate() - 1);
  let n = 0;
  while (prayedCount(d) === 5) { n++; d.setDate(d.getDate() - 1); }
  return n;
}

export function lastDays(n: number, now = new Date()) {
  return Array.from({ length: n }, (_, i) => {
    const d = new Date(now); d.setDate(d.getDate() - (n - 1 - i));
    return { date: d, count: prayedCount(d) };
  });
}
