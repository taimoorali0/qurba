// ===== QURBA: on-device Quran state (bookmarks, last read, reader settings) =====
import { reactive, watch } from 'vue';

export interface LastRead { key: string; surah: number; ayah: number; name: string; at: number }
export type ReaderMode = 'translation' | 'mushaf';
export interface ReaderSettings { fontSize: number; mode: ReaderMode; translationIds: number[] }

function load<T>(k: string, fallback: T): T {
  try { const v = localStorage.getItem(k); return v ? { ...fallback, ...JSON.parse(v) } as T : fallback; } catch { return fallback; }
}
function save(k: string, v: unknown) { try { localStorage.setItem(k, JSON.stringify(v)); } catch {} }

export const readerSettings = reactive<ReaderSettings>(
  load('qurba.reader', { fontSize: 2, mode: 'translation' as ReaderMode, translationIds: [] as number[] }),
);
watch(readerSettings, (v) => save('qurba.reader', v), { deep: true });

// Bookmarks: a Set for the UI + per-key {on, t} so removals sync across devices
const bm = load<{ keys: string[] }>('qurba.bookmarks', { keys: [] });
export const bookmarks = reactive(new Set<string>(bm.keys));
export const bookmarkMeta = reactive<Record<string, { on: boolean; t: number }>>(load('qurba.bookmarksMeta', {} as Record<string, { on: boolean; t: number }>));
for (const k of bm.keys) if (!bookmarkMeta[k]) bookmarkMeta[k] = { on: true, t: Date.now() };

export function toggleBookmark(key: string) {
  const on = !bookmarks.has(key);
  on ? bookmarks.add(key) : bookmarks.delete(key);
  bookmarkMeta[key] = { on, t: Date.now() };
  save('qurba.bookmarks', { keys: [...bookmarks] });
  save('qurba.bookmarksMeta', bookmarkMeta);
}

export function getLastRead(): LastRead | null {
  try { const v = localStorage.getItem('qurba.lastRead'); return v ? JSON.parse(v) : null; } catch { return null; }
}
export function setLastRead(v: LastRead) { save('qurba.lastRead', v); }

export const toArabicDigits = (n: number) => String(n).replace(/\d/g, (d) => '٠١٢٣٤٥٦٧٨٩'[+d]);
