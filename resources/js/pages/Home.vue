<!-- ===== QURBA HOME — START ===== -->
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { BookOpen, Sun, CircleDot, Clock, Compass, GraduationCap, Download, MapPin } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import QurbaShell from '../layouts/QurbaShell.vue';
import { getLastRead, type LastRead } from '../lib/quranLocal';
import { loc } from '../lib/location';
import { countdown, fmtTime, nextPrayer } from '../lib/prayer';
import { computed, onBeforeUnmount } from 'vue';

const { t, locale } = useI18n();
const now = ref(new Date());
let tick: number | undefined;
onMounted(() => { tick = window.setInterval(() => (now.value = new Date()), 30000); });
onBeforeUnmount(() => clearInterval(tick));
const next = computed(() => (loc.place ? nextPrayer(loc.place, now.value) : null));
const tiles = [
  { key: 'nav.quran', href: '/quran', icon: BookOpen },
  { key: 'home.adhkar', href: '/zikr', icon: Sun },
  { key: 'home.tasbeeh', href: '/zikr/tasbeeh', icon: CircleDot },
  { key: 'nav.prayer', href: '/prayer', icon: Clock },
  { key: 'nav.qibla', href: '/qibla', icon: Compass },
  { key: 'nav.learn', href: '/learn', icon: GraduationCap },
  { key: 'home.downloads', href: '/quran/downloads', icon: Download },
];
const lastRead = ref<LastRead | null>(null);
onMounted(() => { lastRead.value = getLastRead(); });
</script>

<template>
  <Head title="Qurba" />
  <QurbaShell>
    <section class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
      <div>
        <h1 class="font-display text-3xl text-emerald-900 md:text-5xl">{{ t('home.greeting') }}</h1>
        <p class="mt-2 text-ink-soft">{{ t('home.sub') }}</p>

        <!-- Next prayer -->
        <div class="mt-6 rounded-[var(--radius-sheet)] bg-emerald-900 p-6 text-cream">
          <p class="text-sm text-gold-200">{{ t('home.nextPrayer') }}</p>
          <template v-if="next && loc.place">
            <p class="mt-2 font-display text-4xl">{{ t('prayer.' + next.name) }} <span class="text-gold-500">{{ fmtTime(next.time, loc.place.tz, locale) }}</span></p>
            <p class="mt-1 text-sm text-gold-200">{{ t('prayer.in', { t: countdown(next.time, now) }) }} · {{ loc.place.label }}</p>
            <Link href="/prayer" class="mt-5 inline-flex items-center gap-2 rounded-full bg-gold-500 px-5 py-2.5 text-sm font-medium text-emerald-950">{{ t('nav.prayer') }}</Link>
          </template>
          <template v-else>
            <p class="mt-3 max-w-sm text-lg">{{ t('home.setLocation') }}</p>
            <Link href="/prayer" class="mt-5 inline-flex items-center gap-2 rounded-full bg-gold-500 px-5 py-2.5 text-sm font-medium text-emerald-950">
              <MapPin class="size-4" /> {{ t('home.setLocationBtn') }}
            </Link>
          </template>
        </div>
      </div>

      <div class="rounded-[var(--radius-sheet)] border border-line bg-paper p-6">
        <h2 class="font-display text-xl text-emerald-900">{{ t('home.continue') }}</h2>
        <template v-if="lastRead">
          <p class="mt-2 text-lg text-ink">{{ lastRead.name }}</p>
          <p class="text-sm text-ink-soft">{{ t('quran.ayah') }} {{ lastRead.ayah }}</p>
          <Link :href="`/quran/${lastRead.surah}#ayah-${lastRead.ayah}`" class="mt-5 inline-flex rounded-full bg-emerald-900 px-5 py-2 text-sm text-cream">
            {{ t('quran.resume') }}
          </Link>
        </template>
        <template v-else>
          <p class="mt-2 text-sm text-ink-soft">{{ t('home.continueEmpty') }}</p>
          <Link href="/quran" class="mt-5 inline-flex rounded-full border border-emerald-900 px-5 py-2 text-sm text-emerald-900">
            {{ t('home.startReading') }}
          </Link>
        </template>
      </div>
    </section>

    <nav class="mt-8 grid grid-cols-4 gap-3 sm:grid-cols-7">
      <Link v-for="tile in tiles" :key="tile.href" :href="tile.href"
        class="group flex flex-col items-center gap-2 rounded-[var(--radius-tile)] border border-line bg-paper px-2 py-4 text-center text-xs transition-colors hover:border-gold-500">
        <component :is="tile.icon" class="size-6 text-emerald-700 group-hover:text-emerald-900" :stroke-width="1.6" />
        {{ t(tile.key) }}
      </Link>
    </nav>
  </QurbaShell>
</template>
<!-- ===== QURBA HOME — END ===== -->
