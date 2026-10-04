<!-- ===== QURBA HOME — START ===== -->
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { BookOpen, Sun, CircleDot, Clock, Compass, GraduationCap, Download, MapPin, ChevronRight, Sunrise, Moon } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import QurbaShell from '../layouts/QurbaShell.vue';
import { getLastRead, type LastRead } from '../lib/quranLocal';
import { loc } from '../lib/location';
import { countdown, fmtTime, nextPrayer, timesFor } from '../lib/prayer';
import { todayTotal } from '../lib/tasbeeh';
import { prayedCount } from '../lib/salahLog';

const { t, locale } = useI18n();
const now = ref(new Date());
let tick: number | undefined;
onMounted(() => { tick = window.setInterval(() => (now.value = new Date()), 30000); });
onBeforeUnmount(() => clearInterval(tick));
const next = computed(() => (loc.place ? nextPrayer(loc.place, now.value) : null));
const today = computed(() => (loc.place ? timesFor(loc.place, now.value) : []));
const prayerIcon = (name: string) => (name === 'sunrise' ? Sunrise : name === 'maghrib' || name === 'isha' ? Moon : Sun);

const tiles = [
  { key: 'nav.quran', desc: 'home.d_quran', href: '/quran', icon: BookOpen },
  { key: 'home.adhkar', desc: 'home.d_adhkar', href: '/zikr/adhkar', icon: Sun },
  { key: 'home.tasbeeh', desc: 'home.d_tasbeeh', href: '/zikr/tasbeeh', icon: CircleDot },
  { key: 'nav.prayer', desc: 'home.d_prayer', href: '/prayer', icon: Clock },
  { key: 'nav.qibla', desc: 'home.d_qibla', href: '/qibla', icon: Compass },
  { key: 'nav.learn', desc: 'home.d_learn', href: '/learn', icon: GraduationCap },
  { key: 'home.downloads', desc: 'home.d_downloads', href: '/quran/downloads', icon: Download },
];
const lastRead = ref<LastRead | null>(null);
const zikrToday = ref(0);
onMounted(() => { lastRead.value = getLastRead(); zikrToday.value = todayTotal(); });
</script>

<template>
  <Head title="Qurba" />
  <QurbaShell>
    <!-- Hero -->
    <section class="relative overflow-hidden rounded-[var(--radius-sheet)] border border-line bg-gradient-to-br from-paper via-cream to-gold-200/60 px-6 py-8 md:px-12 md:py-14">
      <!-- Decorative skyline (domes + minarets), purely ornamental -->
      <svg aria-hidden="true" viewBox="0 0 400 200" class="pointer-events-none absolute bottom-0 end-0 h-40 w-auto text-gold-500/25 md:h-72" fill="currentColor">
        <rect x="40" y="40" width="12" height="160" rx="3" /><path d="M46 14l8 26H38z" />
        <path d="M90 200v-70a60 60 0 01120 0v70z" /><path d="M150 50l6 20h-12z" />
        <rect x="230" y="110" width="110" height="90" /><path d="M230 110h110l-10-14H240z" />
        <rect x="360" y="30" width="12" height="170" rx="3" /><path d="M366 4l8 26h-16z" />
      </svg>

      <div class="relative grid items-center gap-8 lg:grid-cols-[1.3fr_1fr]">
        <div>
          <p class="text-sm text-ink-soft">{{ t('home.greeting') }} · {{ t('home.sub') }}</p>
          <h1 class="mt-2 font-display text-3xl leading-tight text-emerald-900 md:text-5xl">{{ t('home.heroTitle') }}</h1>
          <p class="mt-3 max-w-md text-ink-soft">{{ t('home.heroSub') }}</p>
          <div class="mt-6 flex flex-wrap gap-3">
            <Link href="/quran" class="inline-flex rounded-full bg-emerald-900 px-6 py-2.5 text-sm font-medium text-cream hover:bg-emerald-700">{{ t('home.getStarted') }}</Link>
            <Link href="/learn" class="inline-flex rounded-full border border-line bg-paper px-6 py-2.5 text-sm text-emerald-900 hover:border-gold-500">{{ t('nav.learn') }}</Link>
          </div>
        </div>

        <!-- Next prayer -->
        <div class="rounded-[var(--radius-tile)] bg-emerald-900 p-6 text-cream shadow-lg">
          <p class="text-sm text-gold-200">{{ t('home.nextPrayer') }}</p>
          <template v-if="next && loc.place">
            <p class="mt-2 text-lg">{{ t('prayer.' + next.name) }}</p>
            <p class="font-display text-4xl text-gold-500">{{ fmtTime(next.time, loc.place.tz, locale) }}</p>
            <p class="mt-1 flex items-center gap-1 text-sm text-gold-200"><MapPin class="size-3.5" /> {{ loc.place.label }} · {{ t('prayer.in', { t: countdown(next.time, now) }) }}</p>
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
    <nav class="mt-6 grid grid-cols-4 gap-3 lg:grid-cols-7">
      <Link v-for="tile in tiles" :key="tile.href" :href="tile.href"
        class="group flex flex-col items-center gap-2 rounded-[var(--radius-tile)] border border-line bg-paper px-2 py-4 text-center transition-colors hover:border-gold-500">
        <span class="grid size-11 place-items-center rounded-full bg-emerald-100/60">
          <component :is="tile.icon" class="size-5 text-emerald-700 group-hover:text-emerald-900" :stroke-width="1.7" />
        </span>
        <span class="text-xs font-medium text-ink md:text-sm">{{ t(tile.key) }}</span>
        <span class="hidden text-[11px] leading-snug text-ink-soft md:block">{{ t(tile.desc) }}</span>
      </Link>
    </nav>

    <!-- Dashboard row -->
    <section class="mt-6 grid gap-4 md:grid-cols-3">
      <div class="rounded-[var(--radius-tile)] border border-line bg-paper p-5">
        <h2 class="font-display text-lg text-emerald-900">{{ t('home.continue') }}</h2>
        <template v-if="lastRead">
          <p class="mt-3 text-ink">{{ lastRead.name }}</p>
          <p class="text-sm text-ink-soft">{{ t('quran.ayah') }} {{ lastRead.ayah }}</p>
          <Link :href="`/quran/${lastRead.surah}#ayah-${lastRead.ayah}`" class="mt-4 inline-flex rounded-full bg-emerald-900 px-5 py-2 text-sm text-cream">{{ t('quran.resume') }}</Link>
        </template>
        <template v-else>
          <p class="mt-2 text-sm text-ink-soft">{{ t('home.continueEmpty') }}</p>
          <Link href="/quran" class="mt-4 inline-flex rounded-full border border-emerald-900 px-5 py-2 text-sm text-emerald-900">{{ t('home.startReading') }}</Link>
        </template>
      </div>

      <div class="rounded-[var(--radius-tile)] border border-line bg-paper p-5">
        <div class="flex items-center justify-between">
          <h2 class="font-display text-lg text-emerald-900">{{ t('home.todayPrayers') }}</h2>
          <Link href="/prayer" class="text-xs text-ink-soft hover:text-emerald-900">{{ t('home.viewAll') }}</Link>
        </div>
        <ul v-if="loc.place" class="mt-3 space-y-1 text-sm">
          <li v-for="p in today" :key="p.name" class="flex items-center gap-3 rounded-xl px-3 py-1.5"
            :class="next?.name === p.name && next.time.getTime() === p.time.getTime() ? 'bg-emerald-100/60 font-medium text-emerald-900' : 'text-ink'">
            <component :is="prayerIcon(p.name)" class="size-4 text-gold-600" />
            <span class="flex-1">{{ t('prayer.' + p.name) }}</span>
            <span class="tabular-nums">{{ fmtTime(p.time, loc.place.tz, locale) }}</span>
          </li>
        </ul>
        <p v-else class="mt-2 text-sm text-ink-soft">{{ t('home.setLocation') }}</p>
      </div>

      <div class="rounded-[var(--radius-tile)] border border-line bg-paper p-5">
        <h2 class="font-display text-lg text-emerald-900">{{ t('home.progress') }}</h2>
        <Link href="/prayer" class="mt-3 flex items-center gap-3 rounded-xl border border-line px-3 py-3 hover:border-gold-500">
          <span class="grid size-10 place-items-center rounded-full bg-emerald-700 text-cream"><Clock class="size-5" /></span>
          <span class="flex-1">
            <span class="block text-sm text-ink">{{ t('salah.title') }}</span>
            <span class="text-xs text-ink-soft">{{ t('salah.today', { n: prayedCount(now) }) }}</span>
          </span>
          <ChevronRight class="size-4 text-ink-soft rtl:rotate-180" />
        </Link>
        <Link href="/zikr/tasbeeh" class="mt-2 flex items-center gap-3 rounded-xl border border-line px-3 py-3 hover:border-gold-500">
          <span class="grid size-10 place-items-center rounded-full bg-emerald-900 text-cream"><CircleDot class="size-5" /></span>
          <span class="flex-1">
            <span class="block text-sm text-ink">{{ t('home.tasbeehToday') }}</span>
            <span class="text-xs text-ink-soft">{{ zikrToday }}</span>
          </span>
          <ChevronRight class="size-4 text-ink-soft rtl:rotate-180" />
        </Link>
        <Link href="/zikr/adhkar" class="mt-2 flex items-center gap-3 rounded-xl border border-line px-3 py-3 hover:border-gold-500">
          <span class="grid size-10 place-items-center rounded-full bg-gold-500 text-emerald-950"><Sun class="size-5" /></span>
          <span class="flex-1 text-sm text-ink">{{ t('home.adhkarToday') }}</span>
          <ChevronRight class="size-4 text-ink-soft rtl:rotate-180" />
        </Link>
      </div>
    </section>
  </QurbaShell>
</template>
<!-- ===== QURBA HOME — END ===== -->
