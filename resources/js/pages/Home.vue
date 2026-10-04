<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { BookOpen, Sun, CircleDot, Clock3, Compass, GraduationCap, ArrowRight, MapPin } from 'lucide-vue-next';
import QurbaShell from '../layouts/QurbaShell.vue';
import { getLastRead, type LastRead } from '../lib/quranLocal';
import { loc } from '../lib/location';
import { countdown, fmtTime, nextPrayer } from '../lib/prayer';

const { t, locale } = useI18n();
const now = ref(new Date());
const lastRead = ref<LastRead | null>(null);
let tick: number | undefined;

onMounted(() => {
  lastRead.value = getLastRead();
  tick = window.setInterval(() => (now.value = new Date()), 30000);
});
onBeforeUnmount(() => clearInterval(tick));

const next = computed(() => loc.place ? nextPrayer(loc.place, now.value) : null);
const secondary = [
  { key: 'home.adhkar', href: '/zikr', icon: Sun },
  { key: 'home.tasbeeh', href: '/zikr/tasbeeh', icon: CircleDot },
  { key: 'nav.qibla', href: '/qibla', icon: Compass },
  { key: 'nav.learn', href: '/learn', icon: GraduationCap },
];
</script>

<template>
  <Head title="Qurba" />
  <QurbaShell>
    <section class="mb-7">
      <p class="qurba-eyebrow">{{ t('home.sub') }}</p>
      <h1 class="qurba-title mt-2 max-w-3xl text-4xl leading-[1.05] sm:text-5xl lg:text-6xl">{{ t('home.greeting') }}</h1>
    </section>

    <section class="grid gap-4 lg:grid-cols-[1.35fr_.85fr]">
      <div class="relative overflow-hidden rounded-[2rem] bg-emerald-950 p-6 text-white shadow-[0_24px_60px_rgba(11,59,45,.18)] sm:p-8">
        <div class="pointer-events-none absolute -end-16 -top-20 size-64 rounded-full border border-white/10"></div>
        <div class="pointer-events-none absolute -end-6 -top-6 size-40 rounded-full border border-[#c49e4b]/25"></div>
        <div class="relative flex min-h-[250px] flex-col justify-between">
          <div>
            <p class="text-xs font-bold uppercase tracking-[.14em] text-[#e2c77d]">{{ t('home.nextPrayer') }}</p>
            <template v-if="next && loc.place">
              <div class="mt-5 flex flex-wrap items-end gap-x-5 gap-y-2">
                <h2 class="qurba-title text-5xl text-white sm:text-6xl">{{ t('prayer.' + next.name) }}</h2>
                <p class="pb-1 text-2xl font-semibold text-[#e2c77d]">{{ fmtTime(next.time, loc.place.tz, locale) }}</p>
              </div>
              <p class="mt-3 text-sm text-white/65">{{ t('prayer.in', { t: countdown(next.time, now) }) }} · {{ loc.place.label }}</p>
            </template>
            <template v-else>
              <h2 class="qurba-title mt-5 max-w-xl text-4xl text-white">{{ t('home.setLocation') }}</h2>
              <p class="mt-3 max-w-lg text-sm leading-6 text-white/60">{{ t('home.sub') }}</p>
            </template>
          </div>
          <div class="mt-8">
            <Link href="/prayer" class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-[#d1ad5a] px-4 py-2.5 text-sm font-bold text-emerald-950">
              <Clock3 v-if="loc.place" class="size-4" /><MapPin v-else class="size-4" />
              {{ loc.place ? t('nav.prayer') : t('home.setLocationBtn') }}
            </Link>
          </div>
        </div>
      </div>

      <div class="qurba-surface flex min-h-[250px] flex-col justify-between rounded-[2rem] p-6 sm:p-7">
        <div>
          <div class="flex items-center justify-between">
            <span class="grid size-11 place-items-center rounded-2xl bg-emerald-100 text-emerald-950"><BookOpen class="size-5" /></span>
            <span class="text-xs font-semibold text-ink-soft">{{ t('nav.quran') }}</span>
          </div>
          <p class="qurba-eyebrow mt-7">{{ t('home.continue') }}</p>
          <template v-if="lastRead">
            <h2 class="qurba-title mt-2 text-3xl">{{ lastRead.name }}</h2>
            <p class="mt-1 text-sm text-ink-soft">{{ t('quran.ayah') }} {{ lastRead.ayah }}</p>
          </template>
          <template v-else>
            <h2 class="qurba-title mt-2 text-3xl">{{ t('home.startReading') }}</h2>
            <p class="mt-2 text-sm leading-6 text-ink-soft">{{ t('home.continueEmpty') }}</p>
          </template>
        </div>
        <Link :href="lastRead ? `/quran/${lastRead.surah}#ayah-${lastRead.ayah}` : '/quran'"
          class="mt-7 flex items-center justify-between rounded-xl border border-emerald-950/10 bg-white/70 px-4 py-3 text-sm font-semibold text-emerald-950">
          <span>{{ lastRead ? t('quran.resume') : t('home.startReading') }}</span><ArrowRight class="size-4 rtl:rotate-180" />
        </Link>
      </div>
    </section>

    <section class="mt-8">
      <div class="mb-4 flex items-end justify-between gap-4">
        <div>
          <p class="qurba-eyebrow">Daily essentials</p>
          <h2 class="qurba-title mt-1 text-2xl sm:text-3xl">Your Qurba</h2>
        </div>
      </div>
      <nav class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <Link v-for="item in secondary" :key="item.href" :href="item.href"
          class="qurba-surface group flex min-h-[130px] flex-col justify-between rounded-[1.4rem] p-4 transition hover:-translate-y-0.5 hover:border-emerald-900/20">
          <span class="grid size-10 place-items-center rounded-xl bg-emerald-50 text-emerald-800"><component :is="item.icon" class="size-5" :stroke-width="1.7" /></span>
          <span class="mt-5 flex items-end justify-between gap-2 text-sm font-semibold text-emerald-950">
            {{ t(item.key) }}<ArrowRight class="size-4 opacity-40 transition group-hover:translate-x-0.5 group-hover:opacity-80 rtl:rotate-180" />
          </span>
        </Link>
      </nav>
    </section>
  </QurbaShell>
</template>
