<!-- ===== QURBA: Arabic alphabet (Qaida) — letters, their shapes and vowels, and a listening quiz ===== -->
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronLeft, Volume2 } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import PageTitle from '../components/PageTitle.vue';
import QurbaShell from '../layouts/QurbaShell.vue';
import { forms, harakat, LETTERS, type Letter } from '../lib/qaida';
import { playQaida, qaidaClips, sound, stopOneShot } from '../lib/sounds';

const { t } = useI18n();
onBeforeUnmount(stopOneShot);

// Only qari recordings are played (no computer voice)
const clips = ref(new Set<string>());
onMounted(async () => { clips.value = await qaidaClips(); });
const VOWELS = ['a', 'i', 'u'] as const;
const has = (clip: string) => clips.value.has(clip);
const recordedLetters = computed(() => LETTERS.filter((l) => has(String(l.n))));

const selected = ref<Letter>(LETTERS[0]);
function choose(l: Letter) { selected.value = l; playQaida(String(l.n)); }
const shapes = computed(() => forms(selected.value));
const playing = (l: Letter) => sound.clipPlaying === `qaida-${l.n}`;
const canQuiz = computed(() => recordedLetters.value.length >= 4);

// Listening quiz: hear a letter, pick it from four
const quiz = ref<{ answer: Letter; options: Letter[]; picked: number | null } | null>(null);
const score = ref({ right: 0, total: 0 });
function shuffle<T>(a: T[]) { return [...a].sort(() => Math.random() - 0.5); }
function newQuestion() {
  const pool = recordedLetters.value;
  const answer = pool[Math.floor(Math.random() * pool.length)];
  const others = shuffle(LETTERS.filter((l) => l.n !== answer.n)).slice(0, 3);
  quiz.value = { answer, options: shuffle([answer, ...others]), picked: null };
  playQaida(String(answer.n));
}
function pick(l: Letter) {
  const q = quiz.value; if (!q || q.picked !== null) return;
  q.picked = l.n; score.value.total++;
  if (l.n === q.answer.n) { score.value.right++; if ('vibrate' in navigator) navigator.vibrate?.(20); }
}
</script>

<template>
  <Head :title="t('qaida.title')" />
  <QurbaShell>
    <div class="flex items-center gap-3">
      <Link href="/kids" class="grid size-9 place-items-center rounded-full border border-line bg-paper" :aria-label="t('kids.title')"><ChevronLeft class="size-4 rtl:rotate-180" /></Link>
      <PageTitle :title="t('qaida.title')" arabic="الْقَاعِدَة" :sub="t('qaida.sub')" class="flex-1" />
    </div>
    <p v-if="!recordedLetters.length" class="mt-4 rounded-[var(--radius-tile)] border border-gold-500/40 bg-gold-200/30 p-4 text-sm text-ink">{{ t('qaida.soon') }}</p>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1.4fr_1fr]">
      <div>
      <!-- Phones: the chosen letter stays in view while scrolling the grid -->
      <div class="sticky top-[4.5rem] z-10 mb-3 flex items-center gap-4 rounded-[var(--radius-tile)] bg-gradient-to-br from-teal-950 via-teal-900 to-teal-700 px-5 py-2 text-cream shadow-lift lg:hidden">
        <span lang="ar" class="font-arabic-ui grid h-16 w-14 place-items-center text-5xl font-medium leading-none text-gold-500">{{ selected.char }}</span>
        <span class="min-w-0 flex-1">
          <span lang="ar" class="font-arabic-ui block text-xl leading-snug">{{ selected.name }}</span>
          <span class="text-xs text-gold-200">{{ selected.latin }}</span>
        </span>
        <button v-if="has(String(selected.n))" type="button" class="grid size-11 place-items-center rounded-full bg-gold-500 text-emerald-950" :aria-label="t('qaida.listen')" @click="playQaida(String(selected.n))"><Volume2 class="size-5" /></button>
      </div>
      <!-- Letter grid, right-to-left like a Qaida -->
      <ul dir="rtl" class="grid grid-cols-4 gap-2 sm:grid-cols-7">
        <li v-for="l in LETTERS" :key="l.n">
          <button type="button" class="flex w-full flex-col items-center rounded-2xl border bg-paper py-3 shadow-soft transition-colors"
            :class="selected.n === l.n ? 'border-gold-500 bg-gold-200/40' : 'border-transparent hover:border-gold-500'"
            :aria-label="`${l.latin} ${l.name}`" :aria-pressed="selected.n === l.n" @click="choose(l)">
            <span lang="ar" class="font-quran text-4xl leading-[2.1] text-emerald-900" :class="playing(l) ? 'text-gold-600' : ''">{{ l.char }}</span>
            <span dir="ltr" class="text-[11px] text-ink-soft">{{ l.latin }}</span>
          </button>
        </li>
      </ul>
      </div>

      <!-- Selected letter -->
      <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
        <section class="hidden rounded-[var(--radius-sheet)] bg-gradient-to-br from-teal-950 via-teal-900 to-teal-700 p-6 text-center text-cream shadow-lift lg:block">
          <p lang="ar" class="font-quran text-8xl leading-[1.9] text-gold-500">{{ selected.char }}</p>
          <p lang="ar" dir="rtl" class="font-quran text-2xl leading-[2]">{{ selected.name }}</p>
          <p class="text-sm text-gold-200">{{ selected.latin }}</p>
          <button v-if="has(String(selected.n))" type="button" class="mt-4 inline-flex items-center gap-2 rounded-full bg-gold-500 px-5 py-2 text-sm font-medium text-emerald-950" @click="playQaida(String(selected.n))">
            <Volume2 class="size-4" /> {{ t('qaida.listen') }}
          </button>
          <p v-else class="mt-3 text-xs text-gold-200">{{ t('qaida.noRecording') }}</p>
        </section>

        <section class="rounded-[var(--radius-tile)] bg-paper p-5 shadow-soft">
          <h2 class="font-display text-lg text-emerald-900">{{ t('qaida.shapes') }}</h2>
          <div dir="rtl" class="mt-3 grid grid-cols-4 gap-2 text-center">
            <div v-for="k in (['isolated', 'initial', 'medial', 'final'] as const)" :key="k" class="rounded-xl bg-cream py-2">
              <p lang="ar" class="font-quran text-3xl leading-[2.3] text-emerald-900">{{ shapes[k] }}</p>
              <p dir="ltr" class="text-[11px] text-ink-soft">{{ t('qaida.' + k) }}</p>
            </div>
          </div>
          <p v-if="!selected.joins" class="mt-2 text-xs text-ink-soft">{{ t('qaida.noJoin') }}</p>
        </section>

        <section class="rounded-[var(--radius-tile)] bg-paper p-5 shadow-soft">
          <h2 class="font-display text-lg text-emerald-900">{{ t('qaida.vowels') }}</h2>
          <div dir="rtl" class="mt-3 grid grid-cols-3 gap-2 text-center">
            <button v-for="(h, i) in harakat(selected)" :key="h" type="button" class="rounded-xl bg-cream py-2 enabled:hover:bg-teal-100"
              :disabled="!has(`${selected.n}-${VOWELS[i]}`)" @click="playQaida(`${selected.n}-${VOWELS[i]}`)">
              <span lang="ar" class="block font-quran text-3xl leading-[2.3] text-emerald-900">{{ h }}</span>
              <span dir="ltr" class="text-[11px] text-ink-soft">{{ t('qaida.v' + i) }}</span>
            </button>
          </div>
        </section>
      </aside>
    </div>

    <!-- Quiz -->
    <section v-if="canQuiz" class="mt-8 rounded-[var(--radius-sheet)] bg-gradient-to-br from-lime-100 via-paper to-teal-100 p-6 shadow-soft">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="font-display text-2xl text-emerald-900">{{ t('qaida.quiz') }}</h2>
        <p v-if="score.total" class="text-sm text-ink-soft">{{ t('qaida.score', { r: score.right, n: score.total }) }}</p>
      </div>
      <p class="mt-1 text-sm text-ink-soft">{{ t('qaida.quizSub') }}</p>
      <button v-if="!quiz" type="button" class="mt-4 rounded-full bg-emerald-900 px-6 py-2.5 text-sm text-cream" @click="newQuestion">{{ t('qaida.start') }}</button>
      <template v-else>
        <button type="button" class="mt-4 inline-flex items-center gap-2 rounded-full border border-emerald-900 px-5 py-2 text-sm text-emerald-900" @click="playQaida(String(quiz.answer.n))">
          <Volume2 class="size-4" /> {{ t('qaida.again') }}
        </button>
        <div dir="rtl" class="mt-4 grid grid-cols-4 gap-3">
          <button v-for="o in quiz.options" :key="o.n" type="button" class="rounded-2xl border-2 bg-paper py-4 shadow-soft transition-colors"
            :class="quiz.picked === null ? 'border-transparent hover:border-gold-500' : o.n === quiz.answer.n ? 'border-emerald-500 bg-emerald-100' : quiz.picked === o.n ? 'border-red-300 bg-red-50' : 'border-transparent opacity-60'"
            :aria-label="o.latin" @click="pick(o)">
            <span lang="ar" class="font-quran text-5xl leading-[2] text-emerald-900">{{ o.char }}</span>
          </button>
        </div>
        <div v-if="quiz.picked !== null" class="mt-4 flex flex-wrap items-center gap-3" role="status">
          <p class="font-display text-xl" :class="quiz.picked === quiz.answer.n ? 'text-emerald-700' : 'text-gold-600'">
            {{ quiz.picked === quiz.answer.n ? t('qaida.right') : t('qaida.wrong', { l: `${quiz.answer.latin} (${quiz.answer.char})` }) }}
          </p>
          <button type="button" class="rounded-full bg-emerald-900 px-5 py-2 text-sm text-cream" @click="newQuestion">{{ t('qaida.next') }}</button>
        </div>
      </template>
    </section>
  </QurbaShell>
</template>
<style scoped>
.font-arabic-ui { font-family: 'IBM Plex Sans Arabic', system-ui, sans-serif; }
</style>
