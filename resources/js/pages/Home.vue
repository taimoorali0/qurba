<!-- ===== QURBA HOME — START ===== -->
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { BookOpen, CalendarDays, Coins, ChevronRight, CircleDot, Clock, Compass, GraduationCap, HandHeart, Library, MapPin, Sparkles, Sun, Target } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import DailyAyah from '../components/DailyAyah.vue';
import FridayCard from '../components/FridayCard.vue';
import Ornament from '../components/Ornament.vue';
import RamadanCard from '../components/RamadanCard.vue';
import Rosette from '../components/Rosette.vue';
import QurbaShell from '../layouts/QurbaShell.vue';
import { hijriLabel, isRamadan } from '../lib/hijri';
import { planStatus } from '../lib/khatam';
import { loc } from '../lib/location';
import { countdown, fmtTime, nextPrayer, timesFor } from '../lib/prayer';
import { getLastRead, type LastRead } from '../lib/quranLocal';
import { prayedCount } from '../lib/salahLog';
import { todayTotal } from '../lib/tasbeeh';

const { t, locale } = useI18n();
const now = ref(new Date());
let tick: number | undefined;
onMounted(() => { tick = window.setInterval(() => (now.value = new Date()), 30000); });
onBeforeUnmount(() => clearInterval(tick));
const next = computed(() => (loc.place ? nextPrayer(loc.place, now.value) : null));
const today = computed(() => (loc.place ? timesFor(loc.place, now.value).filter((p) => p.name !== 'sunrise') : []));
const hijriToday = computed(() => hijriLabel(now.value, locale.value));
const ramadan = computed(() => isRamadan(now.value));
const friday = computed(() => now.value.getDay() === 5);

const tiles = [
  { key: 'nav.quran', desc: 'home.d_quran', href: '/quran', icon: BookOpen },
  { key: 'home.adhkar', desc: 'home.d_adhkar', href: '/zikr/adhkar', icon: Sun },
  { key: 'nav.duas', desc: 'home.d_duas', href: '/zikr/adhkar#situations', icon: HandHeart },
  { key: 'home.tasbeeh', desc: 'home.d_tasbeeh', href: '/zikr/tasbeeh', icon: CircleDot },
  { key: 'nav.prayer', desc: 'home.d_prayer', href: '/prayer', icon: Clock },
  { key: 'nav.qibla', desc: 'home.d_qibla', href: '/qibla', icon: Compass },
  { key: 'nav.khatam', desc: 'home.d_khatam', href: '/khatam', icon: Target },
  { key: 'nav.calendar', desc: 'home.d_calendar', href: '/calendar', icon: CalendarDays },
  { key: 'nav.zakat', desc: 'home.d_zakat', href: '/zakat', icon: Coins },
  { key: 'nav.names', desc: 'home.d_names', href: '/names', icon: Sparkles },
  { key: 'nav.learn', desc: 'home.d_learn', href: '/learn', icon: GraduationCap },
  { key: 'nav.explore', desc: 'home.d_library', href: '/explore', icon: Library },
];
// Soft pastel backgrounds for the feature tiles
const tileTones = ['bg-teal-100', 'bg-lime-100', 'bg-olive-100', 'bg-leaf-100'];
const lastRead = ref<LastRead | null>(null);
const zikrToday = ref(0);
const plan = ref<ReturnType<typeof planStatus>>(null);
onMounted(() => { lastRead.value = getLastRead(); zikrToday.value = todayTotal(); plan.value = planStatus(); });
</script>

<template>
  <Head title="" />
  <QurbaShell>
    <!-- Hero -->
    <section class="pattern-gold arch-top relative overflow-hidden rounded-[var(--radius-sheet)] bg-gradient-to-br from-teal-100 via-paper to-lime-100 pt-12 shadow-soft md:pt-20 px-6 py-8 md:px-12 md:py-14">
      <!-- Decorative rosette, purely ornamental -->
      <Rosette class="pointer-events-none absolute -bottom-24 -end-24 size-[26rem] text-teal-400/25 md:-end-10 md:size-[34rem]" />

      <div class="relative grid items-center gap-8 lg:grid-cols-[1.3fr_1fr]">
        <div>
          <p dir="rtl" lang="ar" class="w-fit font-quran text-2xl text-gold-600 md:text-3xl">ٱلسَّلَامُ عَلَيْكُمْ</p>
          <p class="mt-1 text-sm text-ink-soft">{{ t('home.greeting') }} · {{ t('home.sub') }}</p>
          <h1 class="mt-2 font-display text-3xl leading-tight text-emerald-900 md:text-5xl">{{ t('home.heroTitle') }}</h1>
          <Ornament align="start" class="mt-3" />
          <p class="mt-3 max-w-md text-ink-soft">{{ t('home.heroSub') }}</p>
          <div class="mt-6 flex flex-wrap gap-3">
            <Link href="/quran" class="inline-flex rounded-full bg-emerald-900 px-6 py-2.5 text-sm font-medium text-cream hover:bg-emerald-700">{{ t('home.getStarted') }}</Link>
            <Link href="/calendar" class="inline-flex items-center gap-2 rounded-full border border-line bg-paper px-5 py-2.5 text-sm text-emerald-900 hover:border-gold-500">
              <CalendarDays class="size-4" /> {{ hijriToday }}
            </Link>
          </div>
        </div>

        <!-- Next prayer -->
        <div class="isolate overflow-hidden relative rounded-[var(--radius-tile)] bg-gradient-to-br from-teal-950 via-teal-900 to-teal-700 shadow-lift p-6 text-cream">
        <Rosette class="pointer-events-none absolute -end-20 top-1/2 -z-0 size-80 -translate-y-1/2 text-teal-400/20 md:size-96" />
          <p class="text-sm text-gold-200">{{ t('home.nextPrayer') }}</p>
          <template v-if="next && loc.place">
            <p class="mt-2 text-lg">{{ t('prayer.' + next.name) }}</p>
            <p class="font-display text-4xl text-gold-500">{{ fmtTime(next.time, loc.place.tz, locale) }}</p>
            <p class="mt-1 flex items-center gap-1 text-sm text-gold-200"><MapPin class="size-3.5" /> {{ loc.place.label }} · {{ t('prayer.in', { t: countdown(next.time, now) }) }}</p>
            <ul class="mt-4 grid grid-cols-5 gap-1 text-center text-[11px]">
              <li v-for="p in today" :key="p.name" class="rounded-xl py-1.5"
                :class="next.name === p.name && next.time.getTime() === p.time.getTime() ? 'bg-gold-500 text-emerald-950' : 'bg-white/10 text-cream'">
                <span class="block">{{ t('prayer.' + p.name) }}</span>
                <span class="tabular-nums">{{ fmtTime(p.time, loc.place.tz, locale) }}</span>
              </li>
            </ul>
            <Link href="/prayer" class="mt-5 inline-flex items-center gap-1 rounded-full bg-gold-500 px-5 py-2 text-sm font-medium text-emerald-950">
              {{ t('nav.prayer') }} <ChevronRight class="size-4 rtl:rotate-180" />
            </Link>
          </template>
          <template v-else>
            <p class="mt-3 max-w-sm text-lg">{{ t('home.setLocation') }}</p>
            <Link href="/prayer" class="mt-5 inline-flex items-center gap-2 rounded-full bg-gold-500 px-5 py-2.5 text-sm font-medium text-emerald-950">
              <MapPin class="size-4" /> {{ t('home.setLocationBtn') }}
            </Link>
          </template>
        </div>
      </div>
    </section>

    <!-- Feature tiles -->
    <RamadanCard v-if="ramadan" :now="now" class="mt-6" />
    <FridayCard v-if="friday" class="mt-6" />

    <nav :aria-label="t('nav.more')" class="mt-6 grid grid-cols-4 gap-2 sm:grid-cols-6 sm:gap-3">
      <Link v-for="(tile, i) in tiles" :key="tile.href" :href="tile.href"
        class="group flex flex-col items-center gap-2 rounded-[var(--radius-tile)] bg-paper px-1.5 py-4 text-center shadow-soft transition-shadow hover:shadow-lift">
        <span class="grid size-10 place-items-center rounded-2xl sm:size-11" :class="tileTones[i % tileTones.length]">
          <component :is="tile.icon" class="size-5 text-emerald-700 group-hover:text-emerald-900" :stroke-width="1.7" />
        </span>
        <span class="text-[11px] font-medium leading-tight text-ink sm:text-xs md:text-sm">{{ t(tile.key) }}</span>
        <span class="hidden text-[11px] leading-snug text-ink-soft md:block">{{ t(tile.desc) }}</span>
      </Link>
    </nav>

    <!-- Dashboard -->
    <section class="mt-6 grid gap-4 lg:grid-cols-3">
      <DailyAyah class="self-start lg:col-span-2" />

      <div class="space-y-4">
        <div class="rounded-[var(--radius-tile)] bg-paper p-5 shadow-soft">
          <h2 class="font-display text-lg text-emerald-900">{{ t('home.continue') }}</h2>
          <template v-if="lastRead">
            <p class="mt-3 text-ink">{{ lastRead.name }} <span class="text-sm text-ink-soft">· {{ t('quran.ayah') }} {{ lastRead.ayah }}</span></p>
            <Link :href="`/quran/${lastRead.surah}#ayah-${lastRead.ayah}`" class="mt-3 inline-flex rounded-full bg-emerald-900 px-5 py-2 text-sm text-cream hover:bg-emerald-700">{{ t('quran.resume') }}</Link>
          </template>
          <template v-else>
            <p class="mt-2 text-sm text-ink-soft">{{ t('home.continueEmpty') }}</p>
            <Link href="/quran" class="mt-3 inline-flex rounded-full border border-emerald-900 px-5 py-2 text-sm text-emerald-900">{{ t('home.startReading') }}</Link>
          </template>
          <Link href="/khatam" class="mt-4 flex items-center gap-3 rounded-xl border border-line px-3 py-2.5 hover:border-gold-500">
            <Target class="size-5 text-gold-600" />
            <span class="flex-1 text-sm text-ink">{{ plan ? t('khatam.progress', { done: plan.done }) : t('home.d_khatam') }}</span>
            <ChevronRight class="size-4 text-ink-soft rtl:rotate-180" />
          </Link>
        </div>

        <div class="rounded-[var(--radius-tile)] bg-paper p-5 shadow-soft">
          <h2 class="font-display text-lg text-emerald-900">{{ t('home.today') }}</h2>
          <div class="mt-3 grid grid-cols-2 gap-3 text-center">
            <Link href="/prayer" class="rounded-xl bg-cream px-2 py-3 hover:bg-teal-100">
              <p class="font-display text-2xl text-emerald-900">{{ prayedCount(now) }}/5</p>
              <p class="text-xs text-ink-soft">{{ t('home.salahDone') }}</p>
            </Link>
            <Link href="/zikr/tasbeeh" class="rounded-xl bg-cream px-2 py-3 hover:bg-teal-100">
              <p class="font-display text-2xl text-emerald-900">{{ zikrToday }}</p>
              <p class="text-xs text-ink-soft">{{ t('home.tasbeehToday') }}</p>
            </Link>
          </div>
        </div>
      </div>
    </section>
  </QurbaShell>
</template>
<!-- ===== QURBA HOME — END ===== -->
