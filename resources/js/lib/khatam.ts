// ===== QURBA: Khatam (complete Quran) planner, stored on the device =====
import { reactive, watch } from 'vue';
import { daysBetween } from './hijri';

/** Where each juz begins: [surah, ayah] (standard Madani division). */
export const JUZ_START: [number, number][] = [
    [1, 1],
    [2, 142],
    [2, 253],
    [3, 93],
    [4, 24],
    [4, 148],
    [5, 82],
    [6, 111],
    [7, 88],
    [8, 41],
    [9, 93],
    [11, 6],
    [12, 53],
    [15, 1],
    [17, 1],
    [18, 75],
    [21, 1],
    [23, 1],
    [25, 21],
    [27, 56],
    [29, 46],
    [33, 31],
    [36, 28],
    [39, 32],
    [41, 47],
    [46, 1],
    [51, 31],
    [58, 1],
    [67, 1],
    [78, 1],
];
export const PLAN_LENGTHS = [7, 15, 30, 60];

interface Plan {
    days: number;
    start: string;
    done: number[];
}
const KEY = 'qurba.khatam';
function load(): Plan | null {
    try {
        return JSON.parse(localStorage.getItem(KEY) || 'null');
    } catch {
        return null;
    }
}

export const khatam = reactive<{ plan: Plan | null }>({ plan: typeof window === 'undefined' ? null : load() });
watch(
    () => khatam.plan,
    (p) => {
        try {
            if (p) localStorage.setItem(KEY, JSON.stringify(p));
            else localStorage.removeItem(KEY);
        } catch {
            /* storage unavailable */
        }
    },
    { deep: true },
);

export function startPlan(days: number) {
    khatam.plan = { days, start: new Date().toISOString().slice(0, 10), done: [] };
}
export function resetPlan() {
    khatam.plan = null;
}
export function toggleJuz(n: number) {
    const p = khatam.plan;
    if (!p) return;
    p.done = p.done.includes(n) ? p.done.filter((x) => x !== n) : [...p.done, n].sort((a, b) => a - b);
}

/** Day number (1-based, capped at plan length), the juz to reach today, and how far behind the reader is. */
export function planStatus(now = new Date()) {
    const p = khatam.plan;
    if (!p) return null;
    const day = Math.min(p.days, Math.max(1, daysBetween(new Date(p.start + 'T12:00:00'), now) + 1));
    const target = Math.min(30, Math.ceil((30 * day) / p.days));
    const done = p.done.length;
    return { day, days: p.days, target, done, behind: Math.max(0, target - done), finished: done === 30 };
}
