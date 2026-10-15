// ===== QURBA: children's profiles and their stars, stored on the device =====
import { reactive, watch } from 'vue';

export interface Child { id: string; name: string; stars: number[] }
const KEY = 'qurba.kids';

function load(): { children: Child[]; active: string } {
  try {
    const saved = JSON.parse(localStorage.getItem(KEY) || 'null');
    if (saved?.children?.length) return saved;
    // Stars from before profiles existed become the first child's
    const old = JSON.parse(localStorage.getItem('qurba.kidsStars') || '[]');
    return { children: [{ id: 'c1', name: '', stars: Array.isArray(old) ? old : [] }], active: 'c1' };
  } catch { return { children: [{ id: 'c1', name: '', stars: [] }], active: 'c1' }; }
}

export const kids = reactive(typeof window === 'undefined' ? { children: [] as Child[], active: '' } : load());
watch(kids, (v) => { try { localStorage.setItem(KEY, JSON.stringify(v)); } catch { /* storage unavailable */ } }, { deep: true });

export const activeChild = () => kids.children.find((c) => c.id === kids.active) ?? kids.children[0];

export function addChild(name: string) {
  const id = 'c' + Date.now().toString(36);
  kids.children.push({ id, name: name.trim().slice(0, 30), stars: [] });
  kids.active = id;
}
export function removeChild(id: string) {
  if (kids.children.length <= 1) return;
  kids.children = kids.children.filter((c) => c.id !== id);
  if (kids.active === id) kids.active = kids.children[0].id;
}
export function renameChild(id: string, name: string) { const c = kids.children.find((x) => x.id === id); if (c) c.name = name.trim().slice(0, 30); }
/** Toggle a star; returns true when a new star was earned. */
export function toggleStar(surah: number): boolean {
  const c = activeChild(); if (!c) return false;
  if (c.stars.includes(surah)) { c.stars = c.stars.filter((x) => x !== surah); return false; }
  c.stars.push(surah); return true;
}
