<!-- ===== QURBA: Quran Reader (Translation / Mushaf / Tafsir) ===== -->
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Bookmark, BookmarkCheck, ChevronLeft, ChevronRight, List, Loader2, Lock, Moon, Pause, Play, Repeat, Repeat1, Settings2, SkipBack, SkipForward, Volume2, X } from 'lucide-vue-next';
import QurbaShell from '../layouts/QurbaShell.vue';
import { bookmarks, readerSettings, setLastRead, toArabicDigits, toggleBookmark, type ReaderMode } from '../lib/quranLocal';
import { audioPrefs, next as nextAyah, player, prev as prevAyah, setReciter, setSleep, setSpeed, start, stop, toggle, type Reciter } from '../lib/quranAudio';

interface Tr { source_id: number; text: string }
interface Ayah { n: number; key: string; text: string; translations: Tr[] }
interface Source { id: number; name: string; author: string | null; language_code: string; status: string }
interface Surah { id: number; name_simple: string; name_arabic: string | null; name_english: string | null; revelation_place: string | null; ayah_count: number }

const props = defineProps<{
  surah: Surah; bismillah: string | null; ayahs: Ayah[]; translation_sources: Source[];
  prev: number | null; next: number | null; surahs: Surah[]; reciters: Reciter[];
}>();
const { t } = useI18n();

const tab = ref<ReaderMode | 'tafsir'>(readerSettings.mode);
watch(tab, (v) => { if (v !== 'tafsir') readerSettings.mode = v; });
const showSettings = ref(false);
const showList = ref(false);

watch(() => props.translation_sources, (src) => {
  if (!readerSettings.translationIds.length && src.length) readerSettings.translationIds = src.map((s) => s.id);
}, { immediate: true });

const sourceById = computed(() => Object.fromEntries(props.translation_sources.map((s) => [s.id, s])));
const visibleTr = (a: Ayah) => a.translations.filter((tr) => readerSettings.translationIds.includes(tr.source_id));
const isRtl = (lang?: string) => ['ar', 'ur', 'fa'].includes(lang ?? '');
const hasUnreviewed = computed(() => props.translation_sources.some((s) => s.status !== 'approved'));
// ---- Audio ----
const reciter = computed(() => props.reciters.find((r) => r.id === audioPrefs.reciterId) ?? props.reciters[0] ?? null);
watch(reciter, (r) => { setReciter(r); if (r) audioPrefs.reciterId = r.id; }, { immediate: true });
const isThisSurah = computed(() => player.surah === props.surah.id);
const playingAyah = computed(() => (isThisSurah.value && !player.inBismillah ? player.ayah : 0));
function playFrom(n: number) { if (reciter.value) start(props.surah.id, n, props.surah.ayah_count); }
function mainButton() { isThisSurah.value ? toggle() : playFrom(1); }
const repeatNext = { off: 'ayah', ayah: 'surah', surah: 'off' } as const;
watch(playingAyah, async (n) => {
  if (!n) return;
  await nextTick();
  const el = document.getElementById(`ayah-${n}`);
  if (el) { const r = el.getBoundingClientRect(); if (r.top < 160 || r.bottom > window.innerHeight) el.scrollIntoView({ block: 'center', behavior: 'smooth' }); }
});

const place = (s: Surah) => s.revelation_place ? t('quran.' + s.revelation_place) : '';

// ---- Last read: the first ayah whose bottom is below the reading line ----
let raf = 0;
function trackReading() {
  cancelAnimationFrame(raf);
  raf = requestAnimationFrame(() => {
    const line = window.innerHeight * 0.35;
    const els = document.querySelectorAll<HTMLElement>('[data-n]');
    for (const el of els) {
      if (el.getBoundingClientRect().bottom > line) {
        const n = Number(el.dataset.n);
        setLastRead({ key: `${props.surah.id}:${n}`, surah: props.surah.id, ayah: n, name: props.surah.name_simple, at: Date.now() });
        break;
      }
    }
  });
}

async function scrollToHash() {
  await nextTick();
  if (location.hash) document.querySelector(location.hash)?.scrollIntoView({ block: 'start' });
  else window.scrollTo(0, 0);
}

onMounted(() => { scrollToHash(); window.addEventListener('scroll', trackReading, { passive: true }); });
onBeforeUnmount(() => window.removeEventListener('scroll', trackReading));
watch(() => props.surah.id, () => { showList.value = false; scrollToHash(); });
onBeforeUnmount(() => { if (player.playing) return; stop(); });
</script>

<template>
  <Head :title="surah.name_simple" />
  <QurbaShell>
    <div class="lg:grid lg:grid-cols-[17rem_1fr] lg:gap-8">
      <!-- Surah list: sidebar on desktop, drawer on mobile -->
      <aside :class="showList ? 'fixed inset-0 z-40 block bg-cream p-4 pt-6' : 'hidden'"
        class="lg:sticky lg:top-28 lg:block lg:h-[calc(100vh-8rem)] lg:overflow-y-auto lg:bg-transparent lg:p-0">
        <div class="mb-3 flex items-center justify-between lg:hidden">
          <span class="font-display text-xl text-emerald-900">{{ t('nav.quran') }}</span>
          <button class="grid size-9 place-items-center rounded-full border border-line" @click="showList = false" :aria-label="t('quran.close')"><X class="size-4" /></button>
        </div>
        <ul class="h-[calc(100%-3rem)] space-y-1 overflow-y-auto pe-1 lg:h-auto">
          <li v-for="s in surahs" :key="s.id">
            <Link :href="`/quran/${s.id}`"
              class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm"
              :class="s.id === surah.id ? 'bg-emerald-900 text-cream' : 'text-ink hover:bg-paper'">
              <span class="grid size-7 shrink-0 place-items-center rounded-lg text-xs"
                :class="s.id === surah.id ? 'bg-gold-500 text-emerald-950' : 'bg-emerald-100 text-emerald-900'">{{ s.id }}</span>
              <span class="min-w-0 flex-1">
                <span class="block truncate">{{ s.name_simple }}</span>
                <span class="block text-[11px] opacity-70">{{ s.ayah_count }} {{ t('quran.ayahs') }}</span>
              </span>
              <span v-if="s.name_arabic" dir="rtl" lang="ar" class="font-quran text-base">{{ s.name_arabic }}</span>
            </Link>
          </li>
        </ul>
      </aside>

      <article class="min-w-0">
        <!-- Reader header (solid, never shows text behind it) -->
        <header class="sticky top-16 z-20 -mx-4 border-b border-line bg-cream px-4 pb-3 pt-3 md:top-20 lg:mx-0 lg:px-0">
          <div class="flex items-center gap-3">
            <button class="grid size-9 place-items-center rounded-full border border-line bg-paper lg:hidden" @click="showList = true" :aria-label="t('quran.surahList')"><List class="size-4" /></button>
            <div class="min-w-0 flex-1">
              <h1 class="truncate font-display text-xl text-emerald-900 md:text-2xl">{{ surah.name_simple }}</h1>
              <p class="truncate text-xs text-ink-soft">
                <template v-if="surah.name_english">{{ surah.name_english }} | </template>
                <template v-if="surah.revelation_place">{{ place(surah) }} | </template>
                {{ surah.ayah_count }} {{ t('quran.ayahs') }}
              </p>
            </div>
            <span v-if="surah.name_arabic" dir="rtl" lang="ar" class="hidden font-quran text-2xl text-emerald-900 sm:block">{{ surah.name_arabic }}</span>
            <button class="grid size-9 place-items-center rounded-full border border-line bg-paper" @click="showSettings = !showSettings"
              :aria-expanded="showSettings" :aria-label="t('quran.settings')"><Settings2 class="size-4" /></button>
          </div>

          <!-- Tabs -->
          <div role="tablist" class="mt-3 inline-flex rounded-full border border-line bg-paper p-1 text-sm">
            <button v-for="m in (['translation', 'mushaf', 'tafsir'] as const)" :key="m" role="tab" :aria-selected="tab === m"
              class="rounded-full px-4 py-1.5 transition-colors"
              :class="tab === m ? 'bg-emerald-900 text-cream' : 'text-ink-soft hover:text-emerald-900'"
              @click="tab = m">{{ t('quran.' + m) }}</button>
          </div>
        </header>

        <!-- Audio bar -->
        <div v-if="reciter" class="mt-4 rounded-[var(--radius-tile)] border border-line bg-paper px-4 py-3">
          <div class="flex items-center gap-2">
            <button class="grid size-8 place-items-center rounded-full text-ink-soft hover:text-emerald-900 disabled:opacity-40" :disabled="!isThisSurah" @click="prevAyah" :aria-label="t('quran.prevAyah')"><SkipBack class="size-4 rtl:rotate-180" /></button>
            <button class="grid size-11 place-items-center rounded-full bg-emerald-900 text-cream" @click="mainButton" :aria-label="isThisSurah && player.playing ? t('quran.pause') : t('quran.play')">
              <Loader2 v-if="isThisSurah && player.loading" class="size-5 animate-spin" />
              <Pause v-else-if="isThisSurah && player.playing" class="size-5" />
              <Play v-else class="size-5" />
            </button>
            <button class="grid size-8 place-items-center rounded-full text-ink-soft hover:text-emerald-900 disabled:opacity-40" :disabled="!isThisSurah" @click="nextAyah" :aria-label="t('quran.nextAyah')"><SkipForward class="size-4 rtl:rotate-180" /></button>
            <div class="mx-2 min-w-0 flex-1">
              <p class="truncate text-xs text-ink-soft">
                <template v-if="isThisSurah && player.inBismillah">{{ t('quran.bismillah') }}</template>
                <template v-else-if="isThisSurah">{{ t('quran.ayah') }} {{ player.ayah }} / {{ surah.ayah_count }}</template>
                <template v-else>{{ reciter.name }}</template>
              </p>
              <div class="mt-1 h-1 rounded-full bg-line"><div class="h-1 rounded-full bg-gold-500" :style="{ width: (isThisSurah ? player.progress * 100 : 0) + '%' }" /></div>
            </div>
            <button class="grid size-8 place-items-center rounded-full" :class="audioPrefs.repeat !== 'off' ? 'bg-emerald-100 text-emerald-900' : 'text-ink-soft'"
              @click="audioPrefs.repeat = repeatNext[audioPrefs.repeat]" :aria-label="t('quran.repeat_' + audioPrefs.repeat)" :title="t('quran.repeat_' + audioPrefs.repeat)">
              <Repeat1 v-if="audioPrefs.repeat === 'ayah'" class="size-4" /><Repeat v-else class="size-4" />
            </button>
          </div>
          <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
            <label class="flex items-center gap-1.5"><Volume2 class="size-3.5 text-ink-soft" />
              <select v-model.number="audioPrefs.reciterId" class="rounded-full border-line bg-cream py-1 pe-8 ps-3 text-xs">
                <option v-for="r in reciters" :key="r.id" :value="r.id">{{ r.name }}</option>
              </select>
            </label>
            <select :value="audioPrefs.speed" @change="setSpeed(+($event.target as HTMLSelectElement).value)" class="rounded-full border-line bg-cream py-1 pe-8 ps-3 text-xs" :aria-label="t('quran.speed')">
              <option v-for="s in [0.75, 1, 1.25, 1.5]" :key="s" :value="s">{{ s }}×</option>
            </select>
            <label class="flex items-center gap-1.5"><Moon class="size-3.5 text-ink-soft" />
              <select @change="setSleep(+($event.target as HTMLSelectElement).value)" class="rounded-full border-line bg-cream py-1 pe-8 ps-3 text-xs" :aria-label="t('quran.sleep')">
                <option value="0">{{ t('quran.sleepOff') }}</option>
                <option v-for="m in [10, 20, 30, 60]" :key="m" :value="m">{{ m }} min</option>
              </select>
            </label>
            <span v-if="!reciter.approved" class="ms-auto flex items-center gap-1 text-ink-soft"><Lock class="size-3" /> {{ t('quran.audioDev') }}</span>
          </div>
          <p v-if="isThisSurah && player.error" class="mt-2 text-xs text-red-700">{{ t('quran.audioError') }}</p>
        </div>
        <div v-else class="mt-4 flex items-center gap-2 rounded-[var(--radius-tile)] border border-line bg-paper px-4 py-3 text-xs text-ink-soft">
          <Lock class="size-3.5" /> {{ t('quran.audioLocked') }}
        </div>

        <!-- Settings -->
        <section v-if="showSettings" class="mt-4 rounded-[var(--radius-tile)] border border-line bg-paper p-5 text-sm">
          <label class="flex items-center gap-4">
            <span class="w-28 text-ink-soft">{{ t('quran.fontSize') }}</span>
            <input v-model.number="readerSettings.fontSize" type="range" min="1.4" max="3.4" step="0.2" class="flex-1 accent-emerald-900" />
          </label>
          <p class="mt-4 text-ink-soft">{{ t('quran.translation') }}</p>
          <p v-if="!translation_sources.length" class="mt-2 text-ink-soft">{{ t('quran.noTranslations') }}</p>
          <label v-for="s in translation_sources" :key="s.id" class="mt-2 flex items-center gap-3">
            <input v-model="readerSettings.translationIds" :value="s.id" type="checkbox" class="rounded text-emerald-900 focus:ring-gold-500" />
            {{ s.name }} <span class="text-ink-soft">({{ s.language_code }})</span>
          </label>
        </section>

        <p v-if="hasUnreviewed" class="mt-4 rounded-xl bg-gold-200/50 px-4 py-2 text-xs text-ink-soft">{{ t('quran.devReview') }}</p>

        <!-- Bismillah -->
        <p v-if="bismillah && tab !== 'tafsir'" dir="rtl" lang="ar" class="mt-10 text-center font-quran text-emerald-900"
          :style="{ fontSize: readerSettings.fontSize * 0.9 + 'rem', lineHeight: 2.2 }">{{ bismillah }}</p>

        <!-- TRANSLATION mode -->
        <ol v-if="tab === 'translation'" class="mt-6">
          <li v-for="a in ayahs" :id="`ayah-${a.n}`" :key="a.key" :data-n="a.n" class="scroll-mt-48 border-b border-line py-7 transition-colors last:border-0"
            :class="playingAyah === a.n ? '-mx-4 rounded-2xl bg-gold-200/40 px-4' : ''">
            <div class="mb-3 flex items-center justify-between text-xs text-ink-soft">
              <span class="flex items-center gap-2">
                <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-emerald-900">{{ a.key }}</span>
                <button v-if="reciter" class="grid size-7 place-items-center rounded-full hover:bg-paper" @click="playFrom(a.n)" :aria-label="`${t('quran.play')} ${a.key}`"><Play class="size-3.5" /></button>
              </span>
              <button class="grid size-8 place-items-center rounded-full hover:bg-paper" @click="toggleBookmark(a.key)"
                :aria-pressed="bookmarks.has(a.key)" :aria-label="bookmarks.has(a.key) ? t('quran.bookmarked') : t('quran.bookmark')">
                <BookmarkCheck v-if="bookmarks.has(a.key)" class="size-4 text-gold-600" />
                <Bookmark v-else class="size-4" />
              </button>
            </div>
            <p dir="rtl" lang="ar" class="font-quran text-ink" :style="{ fontSize: readerSettings.fontSize + 'rem', lineHeight: 2.4 }">
              {{ a.text }} <span class="whitespace-nowrap text-gold-600">﴿{{ toArabicDigits(a.n) }}﴾</span>
            </p>
            <p v-for="tr in visibleTr(a)" :key="tr.source_id" :dir="isRtl(sourceById[tr.source_id]?.language_code) ? 'rtl' : 'ltr'"
              :lang="sourceById[tr.source_id]?.language_code"
              class="mt-3 text-[0.95rem] text-ink-soft"
              :class="sourceById[tr.source_id]?.language_code === 'ur' ? 'font-[Noto_Nastaliq_Urdu] leading-[2.3]' : 'max-w-prose leading-relaxed'">
              {{ tr.text }}
            </p>
          </li>
        </ol>

        <!-- MUSHAF mode: continuous text, nothing else on the page -->
        <div v-else-if="tab === 'mushaf'" class="mt-6 rounded-[var(--radius-sheet)] border border-line bg-paper px-5 py-8 md:px-10">
          <p dir="rtl" lang="ar" class="text-justify font-quran text-ink" :style="{ fontSize: readerSettings.fontSize + 'rem', lineHeight: 2.5 }">
            <span v-for="a in ayahs" :id="`ayah-${a.n}`" :key="a.key" :data-n="a.n" class="scroll-mt-48 rounded-lg transition-colors" :class="playingAyah === a.n ? 'bg-gold-200/60' : ''">{{ a.text }}
              <button class="whitespace-nowrap text-gold-600" :class="bookmarks.has(a.key) ? 'underline decoration-gold-500 underline-offset-8' : ''"
                @click="toggleBookmark(a.key)" :aria-label="`${t('quran.bookmark')} ${a.key}`">﴿{{ toArabicDigits(a.n) }}﴾</button>
            </span>
          </p>
        </div>

        <!-- TAFSIR: no verified source yet -->
        <div v-else class="mt-6 rounded-[var(--radius-sheet)] border border-line bg-paper px-6 py-10 text-center text-ink-soft">
          {{ t('quran.tafsirSoon') }}
        </div>

        <!-- Prev / next surah -->
        <nav class="mt-8 flex justify-between gap-3">
          <Link v-if="prev" :href="`/quran/${prev}`" class="inline-flex items-center gap-2 rounded-full border border-emerald-900 px-5 py-2 text-sm text-emerald-900">
            <ChevronLeft class="size-4 rtl:rotate-180" /> {{ t('quran.prev') }}
          </Link><span v-else />
          <Link v-if="next" :href="`/quran/${next}`" class="inline-flex items-center gap-2 rounded-full bg-emerald-900 px-5 py-2 text-sm text-cream">
            {{ t('quran.next') }} <ChevronRight class="size-4 rtl:rotate-180" />
          </Link>
        </nav>
      </article>
    </div>
  </QurbaShell>
</template>
