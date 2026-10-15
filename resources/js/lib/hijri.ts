// ===== QURBA: Hijri dates (Umm al-Qura, via the browser's Intl calendar) =====
export interface HijriDate {
    day: number;
    month: number;
    year: number;
}

const numeric = new Intl.DateTimeFormat('en-u-ca-islamic-umalqura-nu-latn', { day: 'numeric', month: 'numeric', year: 'numeric' });

export function hijriOf(date: Date): HijriDate {
    const parts = Object.fromEntries(numeric.formatToParts(date).map((p) => [p.type, p.value]));
    return { day: parseInt(parts.day, 10), month: parseInt(parts.month, 10), year: parseInt(parts.year, 10) };
}

/** Localised long Hijri date, e.g. "12 Rabiʻ I 1448 AH". */
export function hijriLabel(date: Date, locale: string, opts: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'long', year: 'numeric' }) {
    try {
        return date.toLocaleDateString(`${locale}-u-ca-islamic-umalqura`, opts);
    } catch {
        return '';
    }
}

export const isRamadan = (date = new Date()) => hijriOf(date).month === 9;

/** Night events start at Maghrib on the evening before the listed Hijri day. */
export interface IslamicEvent {
    key: string;
    month: number;
    day: number;
    night?: boolean;
}
export const EVENTS: IslamicEvent[] = [
    { key: 'newYear', month: 1, day: 1 },
    { key: 'ashura', month: 1, day: 10 },
    { key: 'mawlid', month: 3, day: 12 },
    { key: 'miraj', month: 7, day: 27, night: true },
    { key: 'shabeBarat', month: 8, day: 15, night: true },
    { key: 'ramadan', month: 9, day: 1 },
    { key: 'qadr', month: 9, day: 27, night: true },
    { key: 'eidFitr', month: 10, day: 1 },
    { key: 'arafah', month: 12, day: 9 },
    { key: 'eidAdha', month: 12, day: 10 },
];

const startOfDay = (d: Date) => new Date(d.getFullYear(), d.getMonth(), d.getDate());
export const daysBetween = (a: Date, b: Date) => Math.round((startOfDay(b).getTime() - startOfDay(a).getTime()) / 86400000);

/** Next occurrence of every event (within ~13 months), soonest first. */
export function upcomingEvents(from = new Date()) {
    const found = new Map<string, { event: IslamicEvent; date: Date; hijri: HijriDate }>();
    const base = startOfDay(from);
    for (let i = 0; i < 390 && found.size < EVENTS.length; i++) {
        const date = new Date(base.getFullYear(), base.getMonth(), base.getDate() + i, 12);
        const h = hijriOf(date);
        for (const event of EVENTS) {
            if (!found.has(event.key) && event.month === h.month && event.day === h.day) found.set(event.key, { event, date, hijri: h });
        }
    }
    return [...found.values()].sort((a, b) => a.date.getTime() - b.date.getTime());
}

/** Gregorian dates of the white days (13–15) in the current or next Hijri month. */
export function nextWhiteDays(from = new Date()) {
    const base = startOfDay(from);
    const out: Date[] = [];
    for (let i = 0; i < 45 && out.length < 3; i++) {
        const date = new Date(base.getFullYear(), base.getMonth(), base.getDate() + i, 12);
        const d = hijriOf(date).day;
        if (d >= 13 && d <= 15) out.push(date);
        else if (out.length) break;
    }
    return out;
}
