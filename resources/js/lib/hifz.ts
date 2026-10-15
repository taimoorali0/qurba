// ===== QURBA: Hifz (memorisation) revision schedule, stored on the device =====
import { reactive, watch } from 'vue';

/** Days until the next revision after each successful review. */
export const INTERVALS = [1, 3, 7, 14, 30, 60];
export interface HifzSurah { surah: number; step: number; next: string; last: string | null }

const KEY = 'qurba.hifz';
function load(): HifzSurah[] { try { return JSON.parse(localStorage.getItem(KEY) || '[]'); } catch { return []; } }
export const hifz = reactive<{ list: HifzSurah[] }>({ list: typeof window === 'undefined' ? [] : load() });
watch(() => hifz.list, (v) => { try { localStorage.setItem(KEY, JSON.stringify(v)); } catch { /* storage unavailable */ } }, { deep: true });

const day = (d = new Date()) => d.toISOString().slice(0, 10);
const addDays = (n: number) => { const d = new Date(); d.setDate(d.getDate() + n); return day(d); };

export function addSurah(surah: number) {
  if (!hifz.list.some((h) => h.surah === surah)) hifz.list.push({ surah, step: 0, next: day(), last: null });
}
export function removeSurah(surah: number) { hifz.list = hifz.list.filter((h) => h.surah !== surah); }

/** Strong review moves the surah further out; a weak one brings it back to tomorrow. */
export function recordReview(surah: number, strong: boolean) {
  const h = hifz.list.find((x) => x.surah === surah); if (!h) return;
  h.step = strong ? Math.min(h.step + 1, INTERVALS.length - 1) : 0;
  h.next = addDays(INTERVALS[h.step]);
  h.last = day();
}

export const isDue = (h: HifzSurah) => h.next <= day();
export const dueList = () => hifz.list.filter(isDue).sort((a, b) => a.next.localeCompare(b.next));
