<!-- ===== QURBA: one adhkar category (counter per item, daily progress) ===== -->
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Check, ChevronLeft, Headphones, Heart, Pause, Play, RotateCcw } from 'lucide-vue-next';
import { categoryIcon } from '../lib/adhkarIcons';
import { playClip, sound, stopOneShot } from '../lib/sounds';
import QurbaShell from '../layouts/QurbaShell.vue';
import PageTitle from '../components/PageTitle.vue';
import { ak, bump, countOf, isFav, resetItem, toggleFav } from '../lib/adhkarLocal';
import { canVibrate, tb } from '../lib/tasbeeh';

interface Word { w: string; a: string | null }
interface Item { id: number; start?: number | null; audio?: string | null; ai?: boolean; words?: Word[]; ar: string; translit: string | null; reference: string | null; repeat: number; status: string; tr: Record<string, string> }
const props = defineProps<{ category: { slug: string; name: Record<string, string>; audio?: string | null; credit?: string | null }; items: Item[]; dev?: boolean; favoritesPage?: boolean;
  categories?: { slug: string; name: Record<string, string> }[] }>();
const { t, locale } = useI18n();

const title = computed(() => props.category.name[locale.value] ?? props.category.name.en);
const catName = (n: Record<string, string>) => n[locale.value] ?? n.en;
const done = (a: Item) => countOf(a.id) >= a.repeat;
const completed = computed(() => props.items.filter(done).length);
const translation = (a: Item) => a.tr[locale.value === 'ar' ? 'en' : locale.value] ?? a.tr.en ?? '';

// ---- Read along: one category recording, the dua being recited is highlighted ----
const ra = ref<HTMLAudioElement | null>(null);
const raPlaying = ref(false);
const raTime = ref(0);
const raDuration = ref(0);
const timed = computed(() => props.items.filter((a) => a.start != null).sort((a, b) => a.start! - b.start!));
const activeId = computed(() => {
  if (!raPlaying.value && raTime.value === 0) return 0;
  let id = 0;
  for (const a of timed.value) if (a.start! <= raTime.value + 0.2) id = a.id;
  return id;
});
watch(activeId, async (id) => {
  if (!id || !raPlaying.value) return;
  await nextTick();
  document.getElementById(`dua-${id}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
});
function raToggle() {
  const el = ra.value; if (!el) return;
  if (el.paused) { stopOneShot(); el.play().catch(() => {}); } else el.pause();
}
function raFrom(a: Item) {
  const el = ra.value; if (!el || a.start == null) return;
  stopOneShot(); el.currentTime = a.start; el.play().catch(() => {});
}
function raSeek(e: Event) { const el = ra.value; if (el) el.currentTime = Number((e.target as HTMLInputElement).value); }
const mmss = (s: number) => `${Math.floor(s / 60)}:${String(Math.floor(s % 60)).padStart(2, '0')}`;
onBeforeUnmount(() => ra.value?.pause());

// ---- Word by word: tap a word to hear it, or play the whole dua word after word with the word highlighted ----
const hasWordVoice = (a: Item) => !!a.words?.length && a.words.every((w) => w.a);
const wordAudio = typeof Audio !== 'undefined' ? new Audio() : null;
const wbw = ref<{ id: number; i: number; all: boolean } | null>(null);
function playWord(a: Item, i: number, all: boolean) {
  const w = a.words?.[i];
  if (!wordAudio || !w?.a) { wbw.value = null; return; }
  ra.value?.pause(); stopOneShot();
  wbw.value = { id: a.id, i, all };
  wordAudio.src = w.a;
  wordAudio.onended = () => {
    if (wbw.value?.id === a.id && wbw.value.all && i + 1 < (a.words?.length ?? 0)) setTimeout(() => playWord(a, i + 1, true), 250);
    else wbw.value = null;
  };
  wordAudio.play().catch(() => (wbw.value = null));
}
function toggleWordByWord(a: Item) {
  if (wbw.value?.id === a.id && wbw.value.all) { wordAudio?.pause(); wbw.value = null; } else playWord(a, 0, true);
}
const isWord = (a: Item, i: number) => wbw.value?.id === a.id && wbw.value.i === i;
onBeforeUnmount(() => wordAudio?.pause());

const clipKey = (a: Item) => `dua-${a.id}`;
function listen(a: Item) { ra.value?.pause(); if (sound.clipPlaying === clipKey(a)) stopOneShot(); else if (a.audio) playClip(clipKey(a), a.audio); }

function tap(a: Item) {
  if (done(a)) return;
  const finished = bump(a.id, a.repeat);
  if (tb.vibrate && canVibrate) navigator.vibrate(finished ? [30, 50, 30] : 10);
}
</script>

<template>
  <Head :title="title" />
  <QurbaShell>
    <div class="lg:grid lg:grid-cols-[15rem_1fr] lg:gap-8">
    <!-- Category sidebar (desktop) -->
    <aside class="hidden lg:block">
      <h2 class="mb-3 font-display text-xl text-emerald-900">{{ t('home.adhkar') }}</h2>
      <nav class="space-y-1">
        <Link v-for="c in categories ?? []" :key="c.slug" :href="`/zikr/adhkar/${c.slug}`"
          class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm"
          :class="c.slug === category.slug ? 'bg-emerald-900 text-cream' : 'text-ink hover:bg-paper'">
          <component :is="categoryIcon(c.slug)" class="size-4" :class="c.slug === category.slug ? 'text-gold-500' : 'text-emerald-700'" />
          {{ catName(c.name) }}
        </Link>
        <Link :href="`/zikr/adhkar-favorites?ids=${ak.favorites.join(',')}`" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm"
          :class="favoritesPage ? 'bg-emerald-900 text-cream' : 'text-ink hover:bg-paper'">
          <Heart class="size-4 text-gold-500" /> {{ t('adhkar.favorites') }}
        </Link>
      </nav>
    </aside>
    <div class="min-w-0">
    <div class="flex items-center gap-3">
      <Link href="/zikr/adhkar" class="grid size-9 lg:hidden place-items-center rounded-full border border-line bg-paper" :aria-label="t('home.adhkar')"><ChevronLeft class="size-4 rtl:rotate-180" /></Link>
      <PageTitle :title="title" arabic="الأذكار" class="flex-1" />
      <label class="flex items-center gap-2 text-sm text-ink-soft"><input v-model="ak.translit" type="checkbox" class="rounded text-emerald-900 focus:ring-gold-500" /> {{ t('adhkar.translit') }}</label>
    </div>

    <!-- Read along -->
    <section v-if="category.audio && items.length" class="sticky top-20 z-20 mt-5 rounded-[var(--radius-tile)] bg-gradient-to-br from-teal-950 via-teal-900 to-teal-700 p-4 text-cream shadow-lift md:top-24">
      <audio ref="ra" :src="category.audio" preload="none" @play="raPlaying = true" @pause="raPlaying = false" @ended="raPlaying = false; raTime = 0"
        @timeupdate="raTime = ($event.target as HTMLAudioElement).currentTime" @loadedmetadata="raDuration = ($event.target as HTMLAudioElement).duration" />
      <div class="flex items-center gap-3">
        <button type="button" class="grid size-12 shrink-0 place-items-center rounded-full bg-gold-500 text-emerald-950" :aria-label="raPlaying ? t('sound.pause') : t('sound.play')" @click="raToggle">
          <Pause v-if="raPlaying" class="size-5" /><Play v-else class="size-5" />
        </button>
        <div class="min-w-0 flex-1">
          <p class="flex items-center gap-1.5 text-sm font-medium"><Headphones class="size-4 text-gold-200" /> {{ t('adhkar.readAlong') }}</p>
          <p v-if="category.credit" class="truncate text-xs text-gold-200">{{ category.credit }}</p>
        </div>
        <span class="text-xs tabular-nums text-gold-200">{{ mmss(raTime) }}<template v-if="raDuration"> / {{ mmss(raDuration) }}</template></span>
      </div>
      <input type="range" min="0" :max="raDuration || 0" step="0.1" :value="raTime" :aria-label="t('adhkar.position')" class="mt-3 w-full accent-[#C9A24A]" @input="raSeek" />
    </section>

    <div v-if="items.length" class="mt-5">
      <p class="text-sm text-ink-soft">{{ t('adhkar.progress', { n: completed, t: items.length }) }}</p>
      <div class="mt-2 h-1.5 rounded-full bg-line"><div class="h-1.5 rounded-full bg-gold-500 transition-all" :style="{ width: (completed / items.length) * 100 + '%' }" /></div>
    </div>
    <p v-else class="mt-8 rounded-[var(--radius-tile)] bg-paper shadow-soft p-6 text-ink-soft">{{ favoritesPage ? t('adhkar.noFavorites') : t('adhkar.empty') }}</p>

    <ol class="mt-6 space-y-4">
      <li v-for="a in items" :id="`dua-${a.id}`" :key="a.id" class="scroll-mt-48 rounded-[var(--radius-sheet)] border bg-paper p-5 transition-all md:p-6"
        :class="activeId === a.id ? 'border-gold-500 ring-2 ring-gold-500/60' : done(a) ? 'border-emerald-700/30 opacity-70' : 'border-line'">
        <div class="flex items-center justify-between text-xs text-ink-soft">
          <span class="flex items-center gap-2">
            <span class="grid size-7 place-items-center rounded-full border border-gold-500 text-[11px] text-gold-600">{{ items.indexOf(a) + 1 }}</span>
            <span v-if="dev && a.status !== 'approved'" class="me-2 rounded-full bg-gold-200 px-2 py-0.5 text-ink">{{ a.status }}</span>
            {{ a.reference }}
          </span>
          <span class="flex items-center gap-1">
          <button v-if="category.audio && a.start != null" class="rounded-full border border-line px-2.5 py-1 text-[11px] text-emerald-900" @click="raFrom(a)">{{ t('adhkar.fromHere') }}</button>
          <span v-if="a.audio && a.ai" class="rounded-full bg-cream px-2 py-0.5 text-[10px] text-ink-soft" :title="t('adhkar.aiVoiceHint')">{{ t('adhkar.aiVoice') }}</span>
          <button v-if="a.audio" class="grid size-8 place-items-center rounded-full border border-line text-emerald-900" @click="listen(a)"
            :aria-label="sound.clipPlaying === clipKey(a) ? t('sound.pause') : t('sound.play')">
            <Pause v-if="sound.clipPlaying === clipKey(a)" class="size-3.5" /><Play v-else class="size-3.5" />
          </button>
          <button class="grid size-8 place-items-center rounded-full" @click="toggleFav(a.id)" :aria-pressed="isFav(a.id)" :aria-label="t('adhkar.favorite')">
            <Heart class="size-4" :class="isFav(a.id) ? 'fill-gold-500 text-gold-600' : ''" />
          </button>
          </span>
        </div>
        <template v-if="hasWordVoice(a)">
          <p dir="rtl" lang="ar" class="mt-3 flex flex-wrap gap-x-1.5 gap-y-1 font-quran text-[1.7rem] leading-[2.3] text-ink">
            <button v-for="(w, i) in a.words" :key="i" type="button" class="rounded-lg px-1 transition-colors"
              :class="isWord(a, i) ? 'bg-gold-200 text-emerald-950' : 'hover:bg-cream'" :aria-label="t('adhkar.hearWord', { w: w.w })" @click="playWord(a, i, false)">{{ w.w }}</button>
          </p>
          <button type="button" class="mt-2 inline-flex items-center gap-1.5 rounded-full border border-line px-3 py-1.5 text-xs text-emerald-900 hover:border-gold-500" @click="toggleWordByWord(a)">
            <Pause v-if="wbw?.id === a.id && wbw.all" class="size-3.5" /><Play v-else class="size-3.5" /> {{ t('adhkar.wordByWord') }}
            <span class="text-ink-soft">· {{ t('adhkar.aiVoice') }}</span>
          </button>
        </template>
        <p v-else dir="rtl" lang="ar" class="mt-3 font-quran text-[1.7rem] leading-[2.3] text-ink">{{ a.ar }}</p>
        <p v-if="ak.translit && a.translit" class="mt-3 text-sm italic text-ink-soft">{{ a.translit }}</p>
        <p v-if="translation(a)" class="mt-3 text-[0.95rem]" :class="locale === 'ur' ? 'font-urdu leading-[2.3]' : 'leading-relaxed'"
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
    </div>
    </div>
  </QurbaShell>
</template>
