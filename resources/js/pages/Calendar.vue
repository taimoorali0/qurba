<!-- ===== QURBA: Islamic (Hijri) calendar with upcoming dates ===== -->
<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { CalendarDays, Moon } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import PageTitle from '../components/PageTitle.vue';
import QurbaShell from '../layouts/QurbaShell.vue';
import { daysBetween, hijriLabel, nextWhiteDays, upcomingEvents } from '../lib/hijri';

const { t, locale } = useI18n();
const now = new Date();
const today = computed(() => ({
    hijri: hijriLabel(now, locale.value),
    greg: now.toLocaleDateString(locale.value, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }),
}));
const events = computed(() => upcomingEvents(now).map((e) => ({ ...e, inDays: daysBetween(now, e.date) })));
const whiteDays = computed(() => nextWhiteDays(now));
const fmt = (d: Date, opts: Intl.DateTimeFormatOptions = { weekday: 'short', day: 'numeric', month: 'short' }) =>
    d.toLocaleDateString(locale.value, opts);
const when = (n: number) => (n === 0 ? t('calendar.isToday') : n === 1 ? t('calendar.tomorrow') : t('calendar.daysLeft', { n }));
</script>

<template>
    <Head :title="t('calendar.title')" />
    <QurbaShell>
        <PageTitle :title="t('calendar.title')" arabic="التقويم الهجري" />

        <section class="mt-6 rounded-[var(--radius-tile)] bg-gradient-to-br from-teal-950 via-teal-900 to-teal-700 p-6 text-cream shadow-lift">
            <p class="text-sm text-gold-200">{{ t('calendar.today') }}</p>
            <p class="mt-1 font-display text-3xl text-gold-500 md:text-4xl">{{ today.hijri }}</p>
            <p class="mt-1 text-sm text-cream/80">{{ today.greg }}</p>
        </section>

        <div class="mt-6 grid gap-6 lg:grid-cols-[1.6fr_1fr]">
            <section class="rounded-[var(--radius-tile)] bg-paper p-5 shadow-soft">
                <h2 class="font-display text-lg text-emerald-900">{{ t('calendar.upcoming') }}</h2>
                <ul class="mt-3 divide-y divide-line">
                    <li v-for="e in events" :key="e.event.key" class="flex items-center gap-4 py-3">
                        <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-teal-100 text-emerald-900">
                            <component :is="e.event.night ? Moon : CalendarDays" class="size-5" :stroke-width="1.7" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-ink">{{ t('calendar.ev.' + e.event.key) }}</p>
                            <p class="text-sm text-ink-soft">
                                {{ hijriLabel(e.date, locale, { day: 'numeric', month: 'long' }) }} · {{ fmt(e.date) }}
                                <span v-if="e.event.night" class="block text-xs">{{ t('calendar.night') }}</span>
                            </p>
                        </div>
                        <span
                            class="shrink-0 rounded-full px-3 py-1 text-xs"
                            :class="e.inDays <= 7 ? 'bg-gold-200 text-emerald-950' : 'bg-cream text-ink-soft'"
                            >{{ when(e.inDays) }}</span
                        >
                    </li>
                </ul>
            </section>

            <aside class="space-y-4">
                <section class="rounded-[var(--radius-tile)] bg-paper p-5 shadow-soft">
                    <h2 class="font-display text-lg text-emerald-900">{{ t('calendar.whiteDays') }}</h2>
                    <ul class="mt-3 flex flex-wrap gap-2">
                        <li v-for="d in whiteDays" :key="d.toISOString()" class="rounded-full bg-cream px-3 py-1.5 text-sm text-ink">{{ fmt(d) }}</li>
                    </ul>
                    <p class="mt-4 text-sm text-ink-soft">{{ t('calendar.sunnahFast') }}</p>
                </section>
                <p class="px-1 text-xs leading-relaxed text-ink-soft">{{ t('calendar.note') }}</p>
            </aside>
        </div>
    </QurbaShell>
</template>
