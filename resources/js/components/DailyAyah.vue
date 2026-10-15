<!-- ===== QURBA: ayah of the day, text from the verified Quran API, shareable as an image ===== -->
<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Share2 } from 'lucide-vue-next';
import { onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

// Short, well-known ayahs that read well on their own (surah, ayah).
const REFS: [number, number][] = [
    [2, 152],
    [2, 186],
    [2, 255],
    [2, 286],
    [3, 139],
    [3, 159],
    [3, 173],
    [7, 56],
    [9, 51],
    [11, 88],
    [13, 28],
    [14, 7],
    [16, 97],
    [17, 80],
    [20, 25],
    [20, 114],
    [21, 87],
    [24, 35],
    [25, 63],
    [29, 69],
    [33, 41],
    [39, 53],
    [40, 60],
    [49, 13],
    [50, 16],
    [55, 13],
    [57, 4],
    [65, 2],
    [65, 3],
    [93, 5],
    [94, 5],
    [94, 6],
    [103, 1],
    [112, 1],
];

interface Daily {
    date: string;
    lang: string;
    surah: number;
    ayah: number;
    name: string;
    text: string;
    translation: string;
}
const { t, locale } = useI18n();
const daily = ref<Daily | null>(null);
const failed = ref(false);
const note = ref('');

const todayKey = () => new Date().toISOString().slice(0, 10);
function pick() {
    const n = Math.floor(Date.now() / 86400000);
    return REFS[n % REFS.length];
}

async function load() {
    const date = todayKey(),
        lang = locale.value;
    try {
        const cached = JSON.parse(localStorage.getItem('qurba.dailyAyah') || 'null') as Daily | null;
        if (cached && cached.date === date && cached.lang === lang) {
            daily.value = cached;
            return;
        }
    } catch {
        /* ignore */
    }
    const [surah, ayah] = pick();
    try {
        const res = await fetch(`/api/v1/quran/surahs/${surah}`, { headers: { Accept: 'application/json' } });
        if (!res.ok) throw new Error(String(res.status));
        const { data } = await res.json();
        const row = data.ayahs.find((a: { n: number }) => a.n === ayah);
        const sources = data.translation_sources as { id: number; language_code: string }[];
        const src = sources.find((s) => s.language_code === lang) ?? sources.find((s) => s.language_code === 'en') ?? sources[0];
        const tr = row?.translations.find((x: { source_id: number }) => x.source_id === src?.id)?.text ?? '';
        if (!row) throw new Error('missing ayah');
        // The first ayah of a surah comes back without the Bismillah, which is what we want here.
        daily.value = { date, lang, surah, ayah, name: data.surah.name_simple, text: row.text, translation: tr.replace(/<[^>]+>/g, '') };
        try {
            localStorage.setItem('qurba.dailyAyah', JSON.stringify(daily.value));
        } catch {
            /* ignore */
        }
    } catch {
        failed.value = true;
    }
}
onMounted(load);
watch(locale, () => {
    failed.value = false;
    load();
});

const ref_ = () => (daily.value ? `${daily.value.name} ${daily.value.surah}:${daily.value.ayah}` : '');

function wrap(ctx: CanvasRenderingContext2D, text: string, max: number) {
    const lines: string[] = [];
    let line = '';
    for (const w of text.split(/\s+/)) {
        const test = line ? `${line} ${w}` : w;
        if (ctx.measureText(test).width > max && line) {
            lines.push(line);
            line = w;
        } else line = test;
    }
    if (line) lines.push(line);
    return lines;
}

async function shareImage(): Promise<Blob | null> {
    const d = daily.value;
    if (!d) return null;
    await document.fonts?.ready;
    const c = document.createElement('canvas');
    c.width = 1080;
    c.height = 1080;
    const ctx = c.getContext('2d');
    if (!ctx) return null;
    const g = ctx.createLinearGradient(0, 0, 1080, 1080);
    g.addColorStop(0, '#0B3B2D');
    g.addColorStop(1, '#14614A');
    ctx.fillStyle = g;
    ctx.fillRect(0, 0, 1080, 1080);
    ctx.strokeStyle = '#C9A24A';
    ctx.lineWidth = 4;
    ctx.strokeRect(48, 48, 984, 984);
    ctx.textAlign = 'center';
    ctx.fillStyle = '#F4E7C5';
    ctx.direction = 'rtl';
    ctx.font = '56px "Amiri Quran", serif';
    let y = 200;
    for (const l of wrap(ctx, d.text, 880)) {
        ctx.fillText(l, 540, y);
        y += 96;
    }
    ctx.direction = 'ltr';
    ctx.fillStyle = '#FFFFFF';
    const rtl = d.lang === 'ur' || d.lang === 'ar';
    ctx.direction = rtl ? 'rtl' : 'ltr';
    ctx.font = rtl ? '36px "Noto Nastaliq Urdu", serif' : '36px Figtree, sans-serif';
    y += 30;
    for (const l of wrap(ctx, d.translation, 880).slice(0, 8)) {
        ctx.fillText(l, 540, y);
        y += rtl ? 70 : 52;
    }
    ctx.direction = 'ltr';
    ctx.fillStyle = '#C9A24A';
    ctx.font = '32px Figtree, sans-serif';
    ctx.fillText(ref_(), 540, 960);
    ctx.fillStyle = '#F4E7C5';
    ctx.font = '28px Marcellus, serif';
    ctx.fillText(location.host, 540, 1010);
    return new Promise((r) => c.toBlob(r, 'image/png'));
}

async function share() {
    const d = daily.value;
    if (!d) return;
    const url = `${location.origin}/quran/${d.surah}#ayah-${d.ayah}`;
    const text = `${d.text}\n\n${d.translation}\n\n— ${ref_()}`;
    try {
        const blob = await shareImage();
        const file = blob ? new File([blob], `qurba-${d.surah}-${d.ayah}.png`, { type: 'image/png' }) : null;
        if (file && navigator.canShare?.({ files: [file] })) {
            await navigator.share({ files: [file], text: `${ref_()} · ${url}` });
            return;
        }
        if (navigator.share) {
            await navigator.share({ text: `${text}\n${url}` });
            return;
        }
        if (file) {
            const a = document.createElement('a');
            a.href = URL.createObjectURL(file);
            a.download = file.name;
            a.click();
            URL.revokeObjectURL(a.href);
        }
        await navigator.clipboard?.writeText(`${text}\n${url}`);
        note.value = t('daily.copied');
        setTimeout(() => (note.value = ''), 2500);
    } catch {
        /* user cancelled */
    }
}
</script>

<template>
    <section class="rounded-[var(--radius-tile)] bg-paper p-5 shadow-soft md:p-6">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-display text-lg text-emerald-900">{{ t('daily.title') }}</h2>
            <button
                v-if="daily"
                type="button"
                class="inline-flex items-center gap-1.5 rounded-full border border-line px-3 py-1.5 text-sm text-emerald-900 hover:border-gold-500"
                @click="share"
            >
                <Share2 class="size-4" /> {{ t('daily.share') }}
            </button>
        </div>
        <template v-if="daily">
            <p dir="rtl" lang="ar" class="mt-4 font-quran text-2xl leading-[2.2] text-emerald-950 md:text-3xl">{{ daily.text }}</p>
            <p v-if="daily.translation" class="mt-3 leading-relaxed text-ink" :dir="daily.lang === 'en' ? 'ltr' : 'rtl'">{{ daily.translation }}</p>
            <div class="mt-4 flex flex-wrap items-center justify-between gap-2 text-sm">
                <span class="text-gold-600">{{ daily.name }} · {{ daily.surah }}:{{ daily.ayah }}</span>
                <Link :href="`/quran/${daily.surah}#ayah-${daily.ayah}`" class="text-emerald-900 underline-offset-4 hover:underline">{{
                    t('daily.read')
                }}</Link>
            </div>
            <p v-if="note" role="status" class="mt-2 text-xs text-ink-soft">{{ note }}</p>
        </template>
        <p v-else-if="failed" class="mt-3 text-sm text-ink-soft">{{ t('daily.error') }}</p>
        <div v-else class="mt-4 space-y-3" aria-hidden="true">
            <div class="h-8 animate-pulse rounded-lg bg-cream" />
            <div class="h-4 w-2/3 animate-pulse rounded bg-cream" />
        </div>
    </section>
</template>
