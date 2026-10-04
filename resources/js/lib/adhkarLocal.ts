// ===== QURBA: adhkar progress (per day) + favorites, on this device =====
import { reactive, watch } from 'vue';
import { today } from './tasbeeh';

interface State { progress: Record<string, Record<string, number>>; favorites: number[]; translit: boolean }
function load(): State {
  const d: State = { progress: {}, favorites: [], translit: true };
  try { const v = localStorage.getItem('qurba.adhkar'); return v ? { ...d, ...JSON.parse(v) } : d; } catch { return d; }
}
export const ak = reactive<State>(load());
export const adhkarUpdatedAt = () => Number(localStorage.getItem('qurba.adhkarAt') || 0);
watch(ak, (v) => {
  // keep 30 days of progress
  const keys = Object.keys(v.progress).sort();
  if (keys.length > 30) for (const k of keys.slice(0, keys.length - 30)) delete v.progress[k];
  try { localStorage.setItem('qurba.adhkar', JSON.stringify(v)); localStorage.setItem('qurba.adhkarAt', String(Date.now())); localStorage.setItem('qurba.syncDirty', '1'); } catch {}
}, { deep: true });

export const countOf = (id: number) => ak.progress[today()]?.[id] ?? 0;
export function bump(id: number, target: number) {
  const d = (ak.progress[today()] ??= {});
  d[id] = Math.min(target, (d[id] ?? 0) + 1);
  return d[id] >= target;
}
export function resetItem(id: number) { const d = ak.progress[today()]; if (d) delete d[id]; }
export const isFav = (id: number) => ak.favorites.includes(id);
export function toggleFav(id: number) { ak.favorites = isFav(id) ? ak.favorites.filter((x) => x !== id) : [...ak.favorites, id]; }
