<!-- ===== QURBA: Kids corner — learn short surahs, collect stars ===== -->
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { BookOpen, HandHeart, Pause, Play, Sparkles, Star } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import QurbaShell from '../layouts/QurbaShell.vue';
import { audioPrefs, player, playQueue, queue, setReciter, toggle, type Reciter } from '../lib/quranAudio';

interface Surah { id: number; name_simple: string; name_arabic: string | null; name_english: string | null; ayah_count: number }
const props = defineProps<{ surahs: Surah[]; reciters: Reciter[] }>();
const { t } = useI18n();

const reciter = computed(() => props.reciters.find((r) => r.id === audioPrefs.reciterId) ?? props.reciters[0] ?? null);
watch(reciter, (r) => setReciter(r), { immediate: true });

// Stars: one per surah the child has learned, kept on this device
const KEY = 'qurba.kidsStars';
const stars = ref<number[]>((() => { try { return JSON.parse(localStorage.getItem(KEY) || '[]'); } catch { return []; } })());
watch(stars, (v) => { try { localStorage.setItem(KEY, JSON.stringify(v)); } catch { /* storage unavailable */ } }, { deep: true });
const cheer = ref('');
function learned(id: number) {
  if (stars.value.includes(id)) { stars.value = stars.value.filter((x) => x !== id); return; }
  stars.value = [...stars.value, id];
  cheer.value = t('kids.cheer'); setTimeout(() => (cheer.value = ''), 2500);
  if ('vibrate' in navigator) navigator.vibrate?.([20, 40, 20]);
}

const playingThis = (id: number) => queue.index >= 0 && queue.key === `kids-${id}`;
function listen(id: number) { if (playingThis(id)) toggle(); else playQueue(`kids-${id}`, [id]); }

const tones = ['bg-teal-100', 'bg-lime-100', 'bg-olive-100', 'bg-leaf-100', 'bg-gold-200/60'];
const links = [
  { href: '/library/kids', key: 'kids.stories', icon: BookOpen },
  { href: '/names', key: 'kids.names', icon: Sparkles },
  { href: '/zikr/adhkar/daily-duas', key: 'kids.duas', icon: HandHeart },
];
</script>

<template>
  <Head :title="t('kids.title')" />
  <QurbaShell>
    <section class="relative overflow-hidden rounded-[var(--radius-sheet)] bg-gradient-to-br from-lime-100 via-paper to-teal-100 p-6 shadow-soft md:p-10">
      <p dir="rtl" lang="ar" class="w-fit font-quran text-2xl text-gold-600">رَبِّ زِدْنِي عِلْمًا</p>
      <h1 class="mt-1 font-display text-3xl text-emerald-900 md:text-4xl">{{ t('kids.title') }}</h1>
      <p class="mt-2 max-w-lg text-ink-soft">{{ t('kids.sub') }}</p>
      <div class="mt-5 inline-flex items-center gap-3 rounded-full bg-paper px-5 py-2.5 shadow-soft">
        <Star class="size-6 fill-gold-500 text-gold-500" />
        <span class="font-display text-2xl text-emerald-900">{{ stars.length }}</span>
        <span class="text-sm text-ink-soft">{{ t('kids.starsOf', { n: surahs.length }) }}</span>
      </div>
      <p v-if="cheer" role="status" class="mt-3 font-display text-xl text-gold-600">{{ cheer }}</p>
    </section>

    <nav class="mt-6 grid grid-cols-3 gap-3">
      <Link v-for="l in links" :key="l.href" :href="l.href" class="flex flex-col items-center gap-2 rounded-[var(--radius-tile)] bg-paper px-2 py-4 text-center shadow-soft hover:shadow-lift">
        <span class="grid size-12 place-items-center rounded-2xl bg-teal-100"><component :is="l.icon" class="size-6 text-emerald-700" /></span>
        <span class="text-sm font-medium text-ink">{{ t(l.key) }}</span>
      </Link>
    </nav>

    <h2 class="mt-8 font-display text-2xl text-emerald-900">{{ t('kids.learn') }}</h2>
    <ul class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <li v-for="(s, i) in surahs" :key="s.id" class="flex flex-col rounded-[var(--radius-tile)] bg-paper p-4 shadow-soft">
        <div class="flex items-center gap-3">
          <span class="grid size-12 shrink-0 place-items-center rounded-2xl font-display text-lg text-emerald-900" :class="tones[i % tones.length]">{{ s.id }}</span>
          <span class="min-w-0 flex-1">
            <span class="block font-medium text-ink">{{ s.name_simple }}</span>
            <span class="text-xs text-ink-soft">{{ t('kids.ayahs', { n: s.ayah_count }) }}</span>
          </span>
          <span v-if="s.name_arabic" dir="rtl" lang="ar" class="font-quran text-xl text-emerald-900">{{ s.name_arabic }}</span>
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
          <button v-if="reciter" type="button" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-900 px-4 py-2 text-sm text-cream" @click="listen(s.id)">
            <Pause v-if="playingThis(s.id) && player.playing" class="size-4" /><Play v-else class="size-4" />
            {{ playingThis(s.id) && player.playing ? t('sound.pause') : t('kids.listen') }}
          </button>
          <Link :href="`/quran/${s.id}`" class="inline-flex items-center gap-1.5 rounded-full border border-line px-4 py-2 text-sm text-emerald-900">{{ t('kids.read') }}</Link>
          <button type="button" class="ms-auto inline-flex items-center gap-1.5 rounded-full px-3 py-2 text-sm transition-colors" :aria-pressed="stars.includes(s.id)"
            :class="stars.includes(s.id) ? 'bg-gold-200 text-emerald-950' : 'border border-line text-ink-soft hover:border-gold-500'" @click="learned(s.id)">
            <Star class="size-4" :class="stars.includes(s.id) ? 'fill-gold-500 text-gold-600' : ''" /> {{ t('kids.learned') }}
          </button>
        </div>
      </li>
    </ul>
  </QurbaShell>
</template>
