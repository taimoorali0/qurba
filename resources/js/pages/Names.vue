<!-- ===== QURBA: 99 Names of Allah (Al-Asma' al-Husna) ===== -->
<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Check, Search } from 'lucide-vue-next';
import QurbaShell from '../layouts/QurbaShell.vue';
import { isMemorised, memorised, NAMES, toggleMemorised } from '../lib/asmaulHusna';
import { toArabicDigits } from '../lib/quranLocal';

const { t } = useI18n();
const q = ref('');
const onlyLeft = ref(false);
const norm = (s: string) => s.toLowerCase().replace(/[^a-z0-9]/g, '');
const shown = computed(() => {
  const term = norm(q.value);
  return NAMES.filter((x) => (!onlyLeft.value || !isMemorised(x.n))
    && (!term || String(x.n) === q.value.trim() || norm(x.tr).includes(term) || norm(x.en).includes(term) || x.ar.includes(q.value.trim())));
});
const done = computed(() => memorised.ids.length);
</script>

<template>
  <Head :title="t('names.title')" />
  <QurbaShell>
    <!-- Header -->
    <section class="relative overflow-hidden rounded-[var(--radius-sheet)] bg-emerald-900 px-6 py-8 text-cream md:px-10">
      <p dir="rtl" lang="ar" class="pointer-events-none absolute -bottom-6 end-4 font-quran text-8xl text-gold-500/15 md:text-9xl" aria-hidden="true">الله</p>
      <h1 class="font-display text-3xl md:text-4xl">{{ t('names.title') }}</h1>
      <p class="mt-2 max-w-xl text-sm text-gold-200">{{ t('names.sub') }}</p>
      <div class="mt-5 max-w-sm">
        <p class="text-xs text-gold-200">{{ t('names.progress', { n: done }) }}</p>
        <div class="mt-1.5 h-1.5 rounded-full bg-emerald-950/60"><div class="h-1.5 rounded-full bg-gold-500 transition-all" :style="{ width: done / 99 * 100 + '%' }" /></div>
      </div>
    </section>

    <!-- Search + filter -->
    <div class="mt-5 flex flex-wrap items-center gap-3">
      <label class="relative min-w-[14rem] flex-1">
        <Search class="pointer-events-none absolute start-4 top-1/2 size-4 -translate-y-1/2 text-ink-soft" />
        <input v-model="q" type="search" :placeholder="t('names.search')" :aria-label="t('names.search')"
          class="w-full rounded-full border-line bg-paper py-2.5 ps-11 pe-4 text-sm focus:border-gold-500 focus:ring-0" />
      </label>
      <label class="flex items-center gap-2 text-sm text-ink-soft">
        <input v-model="onlyLeft" type="checkbox" class="rounded text-emerald-900 focus:ring-gold-500" /> {{ t('names.onlyLeft') }}
      </label>
    </div>

    <!-- Names -->
    <ol class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <li v-for="x in shown" :key="x.n" class="relative rounded-[var(--radius-tile)] border bg-paper p-5 transition-colors"
        :class="isMemorised(x.n) ? 'border-emerald-700/40' : 'border-line'">
        <div class="flex items-start justify-between gap-3">
          <span class="grid size-9 shrink-0 place-items-center rounded-full border border-gold-500 text-xs text-gold-600" :aria-label="`${x.n}`">
            <span lang="ar">{{ toArabicDigits(x.n) }}</span>
          </span>
          <p dir="rtl" lang="ar" class="font-quran text-3xl leading-[1.8] text-emerald-900">{{ x.ar }}</p>
        </div>
        <p class="mt-2 font-medium text-ink">{{ x.tr }}</p>
        <p class="text-sm text-ink-soft">{{ x.en }}</p>
        <button class="mt-4 inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs transition-colors"
          :class="isMemorised(x.n) ? 'border-emerald-700 bg-emerald-700 text-cream' : 'border-line text-ink-soft hover:border-emerald-700 hover:text-emerald-900'"
          :aria-pressed="isMemorised(x.n)" @click="toggleMemorised(x.n)">
          <Check class="size-3.5" /> {{ isMemorised(x.n) ? t('names.memorised') : t('names.markMemorised') }}
        </button>
      </li>
    </ol>
    <p v-if="!shown.length" class="mt-10 text-center text-ink-soft">{{ t('names.none') }}</p>

    <p class="mt-8 text-xs text-ink-soft">{{ t('names.source') }}</p>
  </QurbaShell>
</template>
