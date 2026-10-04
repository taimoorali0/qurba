<!-- ===== QURBA: Salah tracker summary (today, streak, last 7 days) ===== -->
<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Flame } from 'lucide-vue-next';
import { lastDays, prayedCount, streak } from '../lib/salahLog';

const props = defineProps<{ now: Date }>();
const { t, locale } = useI18n();
const week = computed(() => lastDays(7, props.now));
const today = computed(() => prayedCount(props.now));
const run = computed(() => streak(props.now));
const dayName = (d: Date) => d.toLocaleDateString(locale.value, { weekday: 'narrow' });
</script>

<template>
  <section class="rounded-[var(--radius-tile)] bg-paper shadow-soft p-5">
    <div class="flex items-center justify-between">
      <h2 class="font-display text-lg text-emerald-900">{{ t('salah.title') }}</h2>
      <span class="inline-flex items-center gap-1 rounded-full bg-gold-200/60 px-2.5 py-0.5 text-xs text-ink" :title="t('salah.streakHint')">
        <Flame class="size-3.5 text-gold-600" /> {{ t('salah.streak', { n: run }) }}
      </span>
    </div>
    <p class="mt-2 text-sm text-ink-soft">{{ t('salah.today', { n: today }) }}</p>
    <div class="mt-1 h-1.5 rounded-full bg-line"><div class="h-1.5 rounded-full bg-gold-500 transition-all" :style="{ width: today * 20 + '%' }" /></div>
    <ol class="mt-4 grid grid-cols-7 gap-1.5 text-center text-[11px] text-ink-soft" :aria-label="t('salah.week')">
      <li v-for="d in week" :key="d.date.toDateString()">
        <div class="mx-auto flex h-12 w-full max-w-7 items-end overflow-hidden rounded-md bg-cream" :title="`${d.count}/5`">
          <div class="w-full rounded-md" :class="d.count === 5 ? 'bg-emerald-700' : 'bg-gold-500'" :style="{ height: d.count * 20 + '%' }" />
        </div>
        <span class="mt-1 block">{{ dayName(d.date) }}</span>
      </li>
    </ol>
    <p class="mt-3 text-[11px] text-ink-soft">{{ t('salah.local') }}</p>
  </section>
</template>
