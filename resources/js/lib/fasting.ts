// ===== QURBA: Ramadan fasting log, one list of days per Hijri year =====
import { reactive } from 'vue';
import { hijriOf } from './hijri';

const key = (year: number) => `qurba.fasts.${year}`;
function load(year: number): number[] {
    try {
        return JSON.parse(localStorage.getItem(key(year)) || '[]');
    } catch {
        return [];
    }
}

export const fasts = reactive<{ year: number; days: number[] }>({ year: 0, days: [] });

export function loadFasts(now = new Date()) {
    const { year } = hijriOf(now);
    fasts.year = year;
    fasts.days = load(year);
}
export function toggleFast(day: number) {
    fasts.days = fasts.days.includes(day) ? fasts.days.filter((d) => d !== day) : [...fasts.days, day];
    try {
        localStorage.setItem(key(fasts.year), JSON.stringify(fasts.days));
    } catch {
        /* storage unavailable */
    }
}
