<!-- ===== QURBA: Ramadan mode — sehri/iftar countdown and fasting log (shown only in Ramadan) ===== -->
<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Check, Moon, Sunrise } from 'lucide-vue-next';
import { computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { fasts, loadFasts, toggleFast } from '../lib/fasting';
import { hijriOf } from '../lib/hijri';
import { loc } from '../lib/location';
import { countdown, fmtTime, timesFor } from '../lib/prayer';

const props = defineProps<{ now: Date }>();
const { t, locale } = useI18n();
onMounted(() => loadFasts(props.now));

const day = computed(() => hijriOf(props.now).day);
const times = computed(() => {
    if (!loc.place) return null;
    const get = (d: Date, n: string) => timesFor(loc.place!, d).find((p) => p.name === n)!.time;
    const tomorrow = new Date(props.now.getTime() + 86400000);
    const fajr = get(props.now, 'fajr'),
        maghrib = get(props.now, 'maghrib');
    return {
        sehri: fajr > props.now ? fajr : get(tomorrow, 'fajr'),
        iftar: maghrib > props.now ? maghrib : get(tomorrow, 'maghrib'),
    };
});
const fastedToday = computed(() => fasts.days.includes(day.value));
</script>

<template>
    <section
        class="relative overflow-hidden rounded-[var(--radius-tile)] bg-gradient-to-br from-emerald-950 via-teal-900 to-emerald-700 p-5 text-cream shadow-lift md:p-6"
    >
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="font-display text-2xl text-gold-200">{{ t('ramadan.title') }}</h2>
            <p class="text-sm text-gold-200/80">{{ t('ramadan.day', { n: day }) }}</p>
        </div>
        <div v-if="times && loc.place" class="mt-4 grid grid-cols-2 gap-3">
            <div
                v-for="item in [
                    { k: 'sehri', at: times.sehri, icon: Sunrise },
                    { k: 'iftar', at: times.iftar, icon: Moon },
                ]"
                :key="item.k"
                class="rounded-2xl bg-white/10 p-4"
            >
                <p class="flex items-center gap-1.5 text-sm text-gold-200">
                    <component :is="item.icon" class="size-4" /> {{ t('ramadan.' + item.k) }}
                </p>
                <p class="mt-1 font-display text-3xl tabular-nums text-white">{{ fmtTime(item.at, loc.place.tz, locale) }}</p>
                <p class="text-xs text-gold-200/80">{{ t('ramadan.in', { t: countdown(item.at, now) }) }}</p>
            </div>
        </div>
        <Link v-else href="/prayer" class="mt-4 inline-flex rounded-full bg-gold-500 px-5 py-2 text-sm font-medium text-emerald-950">{{
            t('home.setLocationBtn')
        }}</Link>
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gold-200/90">{{ t('ramadan.marked', { n: fasts.days.length }) }}</p>
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium transition-colors"
                :class="fastedToday ? 'bg-gold-500 text-emerald-950' : 'border border-gold-200/50 text-gold-200 hover:bg-white/10'"
                :aria-pressed="fastedToday"
                @click="toggleFast(day)"
            >
                <Check class="size-4" /> {{ t('ramadan.fasted') }}
            </button>
        </div>
    </section>
</template>
