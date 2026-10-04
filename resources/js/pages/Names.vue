<!-- ===== QURBA: 99 Names of Allah (Al-Asma' al-Husna) ===== -->
<script setup lang="ts">
import Rosette from '../components/Rosette.vue';
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Check, Pause, Play, Search, Volume2 } from 'lucide-vue-next';
import QurbaShell from '../layouts/QurbaShell.vue';
import Ornament from '../components/Ornament.vue';
import { isMemorised, memorised, NAMES, toggleMemorised } from '../lib/asmaulHusna';
import { toArabicDigits } from '../lib/quranLocal';
import { canSpeakArabic, nameVoices, playClip, playName, sound, stopOneShot, type VoiceInfo } from '../lib/sounds';

const { t } = useI18n();
const q = ref('');
const onlyLeft = ref(false);
const norm = (s: string) => s.toLowerCase().replace(/[^a-z0-9]/g, '');
const shown = computed(() => {
  const term = norm(q.value);
  return NAMES.filter((x) => (!onlyLeft.value || !isMemorised(x.n))
    && (!term || String(x.n) === q.value.trim() || norm(x.tr).includes(term) || norm(x.en).includes(term) || x.ar.includes(q.value.trim())));
});
const done = computed(() => memorised.ids.length);

// Recitations appear only once public/audio/names/*.mp3 have been added
const playingAll = ref(false);
// Voices: recorded sets (standard, kids, …) plus the device's own Arabic voice
const voices = ref<Record<string, VoiceInfo>>({});
const voice = ref<string>((() => { try { return localStorage.getItem('qurba.nameVoice') ?? 'standard'; } catch { return 'standard'; } })());
watch(voice, (v) => { try { localStorage.setItem('qurba.nameVoice', v); } catch {} stopOneShot(); playingAll.value = false; });
const available = computed(() => Object.entries(voices.value).filter(([, v]) => v.names.length || v.full));
const current = computed(() => voices.value[voice.value]);
const hasRecordings = computed(() => !!current.value && (current.value.names.length > 0 || !!current.value.full));
const hasFull = computed(() => !!current.value?.full);
const hasAudio = ref(false);
onMounted(async () => {
  voices.value = await nameVoices();
  // Fall back to the first voice that has recordings, or the device voice
  if (voice.value && !available.value.some(([k]) => k === voice.value)) voice.value = available.value[0]?.[0] ?? '';
  hasAudio.value = available.value.length > 0 || canSpeakArabic();
});
const arabicOf = (n: number) => NAMES[n - 1]?.ar ?? '';
// The complete recitation ending also ends 'Play all'
watch(() => sound.clipPlaying, (k, old) => { if (old === 'names-full' && !k) playingAll.value = false; });
function listen(n: number) {
  playingAll.value = false;
  if (sound.namePlaying === n) stopOneShot(); else playName(n, undefined, arabicOf(n), voice.value);
}
function playAll(from = 1) {
  if (playingAll.value && from === 1) { playingAll.value = false; stopOneShot(); return; }
  // One complete recitation of all 99 names, when uploaded
  if (hasFull.value) {
    playingAll.value = true;
    playClip('names-full', `/audio/${current.value!.full}`).then((ok) => { if (!ok) playingAll.value = false; });
    return;
  }
  playingAll.value = true;
  const step = (n: number) => {
    if (!playingAll.value || n > 99) { playingAll.value = false; return; }
    document.getElementById(`name-${n}`)?.scrollIntoView({ block: 'center', behavior: 'smooth' });
    playName(n, () => window.setTimeout(() => step(n + 1), 350), arabicOf(n), voice.value).then((ok) => { if (!ok) playingAll.value = false; });
  };
  step(from);
}
</script>

<template>
  <Head :title="t('names.title')" />
  <QurbaShell>
    <!-- Header -->
    <section class="isolate arch-top relative overflow-hidden rounded-[var(--radius-sheet)] bg-gradient-to-br from-teal-950 via-teal-900 to-teal-700 shadow-lift px-6 py-8 text-cream md:px-10">
        <Rosette class="pointer-events-none absolute -end-20 top-1/2 -z-0 size-80 -translate-y-1/2 text-teal-400/20 md:size-96" />
      <p dir="rtl" lang="ar" class="pointer-events-none absolute -bottom-6 end-4 font-quran text-8xl text-gold-500/15 md:text-9xl" aria-hidden="true">الله</p>
      <p dir="rtl" lang="ar" class="pt-6 text-center font-quran text-3xl text-gold-200 md:pt-10 md:text-4xl">أَسْمَاءُ ٱللَّهِ ٱلْحُسْنَىٰ</p>
      <h1 class="mt-2 text-center font-display text-3xl md:text-4xl">{{ t('names.title') }}</h1>
      <Ornament light class="mt-3" />
      <p class="mx-auto mt-3 max-w-xl text-center text-sm text-gold-200">{{ t('names.sub') }}</p>
      <div class="mx-auto mt-5 max-w-sm text-center">
        <p class="text-xs text-gold-200">{{ t('names.progress', { n: done }) }}</p>
        <div class="mt-1.5 h-1.5 rounded-full bg-emerald-950/60"><div class="h-1.5 rounded-full bg-gold-500 transition-all" :style="{ width: done / 99 * 100 + '%' }" /></div>
      </div>
    </section>

    <!-- Search + filter -->
    <div class="mt-5 flex flex-wrap items-center gap-3">
      <label class="relative min-w-[14rem] flex-1">
        <Search class="pointer-events-none absolute start-4 top-1/2 size-4 -translate-y-1/2 text-ink-soft" />
        <input v-model="q" type="search" :placeholder="t('names.search')" :aria-label="t('names.search')"
          class="w-full rounded-full border-line bg-paper py-2.5 ps-11 pe-4 text-sm focus:border-gold-500 focus:ring-0" />
      </label>
      <div v-if="hasAudio" class="flex flex-wrap items-center gap-1 rounded-full bg-paper p-1 text-sm shadow-soft" role="radiogroup" :aria-label="t('names.voice')">
        <button v-for="[key, v] in available" :key="key" role="radio" :aria-checked="voice === key"
          class="rounded-full px-3 py-1.5 transition-colors" :class="voice === key ? 'bg-emerald-900 text-cream' : 'text-ink-soft hover:text-emerald-900'"
          @click="voice = key">{{ key === 'kids' ? '🧒 ' : '' }}{{ t('names.voice_' + key, v.label) }}</button>
        <button v-if="canSpeakArabic()" role="radio" :aria-checked="voice === ''"
          class="rounded-full px-3 py-1.5 transition-colors" :class="voice === '' ? 'bg-emerald-900 text-cream' : 'text-ink-soft hover:text-emerald-900'"
          @click="voice = ''">{{ t('names.voice_device') }}</button>
      </div>
      <button v-if="hasAudio" class="inline-flex items-center gap-2 rounded-full bg-emerald-900 px-4 py-2 text-sm text-cream" @click="playAll()">
        <Pause v-if="playingAll" class="size-4" /><Volume2 v-else class="size-4" /> {{ playingAll ? t('names.stop') : t('names.playAll') }}
      </button>
      <label class="flex items-center gap-2 text-sm text-ink-soft">
        <input v-model="onlyLeft" type="checkbox" class="rounded text-emerald-900 focus:ring-gold-500" /> {{ t('names.onlyLeft') }}
      </label>
    </div>

    <!-- Names -->
    <ol class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <li v-for="x in shown" :id="`name-${x.n}`" :key="x.n" class="relative rounded-[var(--radius-tile)] border bg-paper p-5 transition-colors"
        :class="sound.namePlaying === x.n ? 'border-gold-500 ring-2 ring-gold-200' : isMemorised(x.n) ? 'border-emerald-700/40' : 'border-line'">
        <div class="flex items-start justify-between gap-3">
          <span class="grid size-9 shrink-0 place-items-center rounded-full border border-gold-500 text-xs text-gold-600" :aria-label="`${x.n}`">
            <span lang="ar">{{ toArabicDigits(x.n) }}</span>
          </span>
          <p dir="rtl" lang="ar" class="font-quran text-3xl leading-[1.8] text-emerald-900">{{ x.ar }}</p>
        </div>
        <p class="mt-2 font-medium text-ink">{{ x.tr }}</p>
        <p class="text-sm text-ink-soft">{{ x.en }}</p>
        <div class="mt-4 flex items-center gap-2">
        <button v-if="hasAudio" class="grid size-8 place-items-center rounded-full border border-line text-emerald-900 hover:border-emerald-700"
          :aria-label="`${t('names.listen')} ${x.tr}`" @click="listen(x.n)">
          <Pause v-if="sound.namePlaying === x.n" class="size-3.5" /><Play v-else class="size-3.5" />
        </button>
        <button class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs transition-colors"
          :class="isMemorised(x.n) ? 'border-emerald-700 bg-emerald-700 text-cream' : 'border-line text-ink-soft hover:border-emerald-700 hover:text-emerald-900'"
          :aria-pressed="isMemorised(x.n)" @click="toggleMemorised(x.n)">
          <Check class="size-3.5" /> {{ isMemorised(x.n) ? t('names.memorised') : t('names.markMemorised') }}
        </button>
        </div>
      </li>
    </ol>
    <p v-if="!shown.length" class="mt-10 text-center text-ink-soft">{{ t('names.none') }}</p>

    <p v-if="hasAudio && (!hasRecordings || current.names.length < 99)" class="mt-8 text-xs text-ink-soft">{{ t('names.deviceVoice') }}</p>
    <p class="mt-2 text-xs text-ink-soft">{{ t('names.source') }}</p>
  </QurbaShell>
</template>
