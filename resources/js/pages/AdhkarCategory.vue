<!-- ===== QURBA: one adhkar category (counter per item, daily progress) ===== -->
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Check, ChevronLeft, Heart, RotateCcw } from 'lucide-vue-next';
import QurbaShell from '../layouts/QurbaShell.vue';
import { ak, bump, countOf, isFav, resetItem, toggleFav } from '../lib/adhkarLocal';
import { canVibrate, tb } from '../lib/tasbeeh';

interface Item { id: number; ar: string; translit: string | null; reference: string | null; repeat: number; status: string; tr: Record<string, string> }
const props = defineProps<{ category: { slug: string; name: Record<string, string> }; items: Item[]; dev?: boolean; favoritesPage?: boolean }>();
const { t, locale } = useI18n();

const title = computed(() => props.category.name[locale.value] ?? props.category.name.en);
const done = (a: Item) => countOf(a.id) >= a.repeat;
const completed = computed(() => props.items.filter(done).length);
const translation = (a: Item) => a.tr[locale.value === 'ar' ? 'en' : locale.value] ?? a.tr.en ?? '';

function tap(a: Item) {
  if (done(a)) return;
  const finished = bump(a.id, a.repeat);
  if (tb.vibrate && canVibrate) navigator.vibrate(finished ? [30, 50, 30] : 10);
}
</script>

<template>
  <Head :title="title" />
  <QurbaShell>
    <div class="flex items-center gap-3">
      <Link href="/zikr/adhkar" class="grid size-9 place-items-center rounded-full border border-line bg-paper" :aria-label="t('home.adhkar')"><ChevronLeft class="size-4 rtl:rotate-180" /></Link>
      <h1 class="flex-1 font-display text-3xl text-emerald-900">{{ title }}</h1>
      <label class="flex items-center gap-2 text-sm text-ink-soft"><input v-model="ak.translit" type="checkbox" class="rounded text-emerald-900 focus:ring-gold-500" /> {{ t('adhkar.translit') }}</label>
    </div>

    <div v-if="items.length" class="mt-5">
      <p class="text-sm text-ink-soft">{{ t('adhkar.progress', { n: completed, t: items.length }) }}</p>
      <div class="mt-2 h-1.5 rounded-full bg-line"><div class="h-1.5 rounded-full bg-gold-500 transition-all" :style="{ width: (completed / items.length) * 100 + '%' }" /></div>
    </div>
    <p v-else class="mt-8 rounded-[var(--radius-tile)] border border-line bg-paper p-6 text-ink-soft">{{ favoritesPage ? t('adhkar.noFavorites') : t('adhkar.empty') }}</p>

    <ol class="mt-6 space-y-4">
      <li v-for="a in items" :key="a.id" class="rounded-[var(--radius-sheet)] border bg-paper p-5 transition-opacity md:p-6"
        :class="done(a) ? 'border-emerald-700/30 opacity-70' : 'border-line'">
        <div class="flex items-center justify-between text-xs text-ink-soft">
          <span>
            <span v-if="dev && a.status !== 'approved'" class="me-2 rounded-full bg-gold-200 px-2 py-0.5 text-ink">{{ a.status }}</span>
            {{ a.reference }}
          </span>
          <button class="grid size-8 place-items-center rounded-full" @click="toggleFav(a.id)" :aria-pressed="isFav(a.id)" :aria-label="t('adhkar.favorite')">
            <Heart class="size-4" :class="isFav(a.id) ? 'fill-gold-500 text-gold-600' : ''" />
          </button>
        </div>
        <p dir="rtl" lang="ar" class="mt-3 font-quran text-[1.7rem] leading-[2.3] text-ink">{{ a.ar }}</p>
        <p v-if="ak.translit && a.translit" class="mt-3 text-sm italic text-ink-soft">{{ a.translit }}</p>
        <p v-if="translation(a)" class="mt-3 text-[0.95rem]" :class="locale === 'ur' ? 'font-[Noto_Nastaliq_Urdu] leading-[2.3]' : 'leading-relaxed'"
          :dir="locale === 'ur' ? 'rtl' : 'ltr'">{{ translation(a) }}</p>

        <div class="mt-5 flex items-center gap-3">
          <button class="flex flex-1 items-center justify-center gap-2 rounded-full py-3 text-sm [touch-action:manipulation]"
            :class="done(a) ? 'bg-emerald-100 text-emerald-900' : 'bg-emerald-900 text-cream active:scale-[0.99]'" @click="tap(a)">
            <Check v-if="done(a)" class="size-4" />
            {{ done(a) ? t('adhkar.done') : t('adhkar.tap') }}
            <span class="tabular-nums">{{ countOf(a.id) }} / {{ a.repeat }}</span>
          </button>
          <button v-if="countOf(a.id)" class="grid size-11 place-items-center rounded-full border border-line" @click="resetItem(a.id)" :aria-label="t('tasbeeh.reset')"><RotateCcw class="size-4" /></button>
        </div>
      </li>
    </ol>
  </QurbaShell>
</template>
