<!-- ===== QURBA: Hifz revision — hide the text, recite from memory, reveal to check ===== -->
<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { CalendarClock, Check, Eye, EyeOff, Plus, RotateCcw, Trash2, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import PageTitle from '../components/PageTitle.vue';
import QurbaShell from '../layouts/QurbaShell.vue';
import { addSurah, dueList, hifz, isDue, recordReview, removeSurah } from '../lib/hifz';

const props = defineProps<{ surahs: { id: number; name_simple: string; ayah_count: number }[] }>();
const { t, locale } = useI18n();
const name = (id: number) => props.surahs.find((s) => s.id === id)?.name_simple ?? `Surah ${id}`;

const pick = ref(114);
const due = computed(() => dueList());
const sorted = computed(() => [...hifz.list].sort((a, b) => a.next.localeCompare(b.next)));
const fmtDay = (d: string) => new Date(d + 'T12:00:00').toLocaleDateString(locale.value, { day: 'numeric', month: 'short' });

// Review session
interface Ayah { n: number; text: string }
const session = ref<{ surah: number; ayahs: Ayah[]; bismillah: string | null } | null>(null);
const shown = ref(new Set<number>());
const hint = ref(true);
const loading = ref(false);
const failed = ref(false);

async function review(surah: number) {
  loading.value = true; failed.value = false; shown.value = new Set();
  try {
    const res = await fetch(`/api/v1/quran/surahs/${surah}`, { headers: { Accept: 'application/json' } });
    if (!res.ok) throw new Error(String(res.status));
    const { data } = await res.json();
    session.value = { surah, bismillah: data.bismillah, ayahs: data.ayahs.map((a: { n: number; text: string }) => ({ n: a.n, text: a.text })) };
    window.scrollTo({ top: 0, behavior: 'smooth' });
  } catch { failed.value = true; } finally { loading.value = false; }
}
const firstWord = (s: string) => s.split(/\s+/)[0] ?? '';
function reveal(n: number) { const s = new Set(shown.value); if (s.has(n)) s.delete(n); else s.add(n); shown.value = s; }
const allShown = computed(() => session.value && shown.value.size === session.value.ayahs.length);
function revealAll() { shown.value = allShown.value ? new Set() : new Set(session.value?.ayahs.map((a) => a.n)); }
function finish(strong: boolean) { if (!session.value) return; addSurah(session.value.surah); recordReview(session.value.surah, strong); session.value = null; }
</script>

<template>
  <Head :title="t('hifz.title')" />
  <QurbaShell>
    <PageTitle :title="t('hifz.title')" arabic="الحِفْظ" :sub="t('hifz.sub')" />

    <!-- Review in progress -->
    <section v-if="session" class="mt-6">
      <div class="sticky top-20 z-20 flex flex-wrap items-center gap-2 rounded-[var(--radius-tile)] bg-paper/95 p-3 shadow-soft backdrop-blur md:top-24">
        <p class="me-auto font-display text-lg text-emerald-900">{{ name(session.surah) }}</p>
        <button type="button" class="inline-flex items-center gap-1.5 rounded-full border border-line px-3 py-1.5 text-sm" :aria-pressed="hint" @click="hint = !hint">
          {{ hint ? t('hifz.hintOn') : t('hifz.hintOff') }}
        </button>
        <button type="button" class="inline-flex items-center gap-1.5 rounded-full border border-line px-3 py-1.5 text-sm" @click="revealAll">
          <EyeOff v-if="allShown" class="size-4" /><Eye v-else class="size-4" /> {{ allShown ? t('hifz.hideAll') : t('hifz.showAll') }}
        </button>
        <button type="button" class="grid size-9 place-items-center rounded-full border border-line" :aria-label="t('hifz.cancel')" @click="session = null"><X class="size-4" /></button>
      </div>

      <p class="mt-4 text-sm text-ink-soft">{{ t('hifz.howTo') }}</p>
      <p v-if="session.bismillah" dir="rtl" lang="ar" class="mt-4 text-center font-quran text-2xl text-emerald-900">{{ session.bismillah }}</p>
      <ol class="mt-4 space-y-3">
        <li v-for="a in session.ayahs" :key="a.n">
          <button type="button" class="w-full rounded-[var(--radius-tile)] border bg-paper p-4 text-start shadow-soft transition-colors"
            :class="shown.has(a.n) ? 'border-emerald-500' : 'border-transparent hover:border-gold-500'" :aria-expanded="shown.has(a.n)" @click="reveal(a.n)">
            <span class="flex items-center gap-3">
              <span class="grid size-8 shrink-0 place-items-center rounded-full border border-gold-500 text-xs text-gold-600">{{ a.n }}</span>
              <span v-if="shown.has(a.n)" dir="rtl" lang="ar" class="flex-1 font-quran text-2xl leading-[2.2] text-ink">{{ a.text }}</span>
              <span v-else dir="rtl" lang="ar" class="flex-1 font-quran text-2xl leading-[2.2] text-ink">
                <template v-if="hint">{{ firstWord(a.text) }} </template><span class="text-ink-soft/50">…</span>
              </span>
            </span>
          </button>
        </li>
      </ol>
      <div class="mt-6 flex flex-wrap gap-3 rounded-[var(--radius-tile)] bg-paper p-5 shadow-soft">
        <p class="w-full text-sm text-ink">{{ t('hifz.howWent') }}</p>
        <button type="button" class="inline-flex items-center gap-2 rounded-full bg-emerald-900 px-5 py-2.5 text-sm text-cream hover:bg-emerald-700" @click="finish(true)"><Check class="size-4" /> {{ t('hifz.strong') }}</button>
        <button type="button" class="inline-flex items-center gap-2 rounded-full border border-line px-5 py-2.5 text-sm text-ink hover:border-gold-500" @click="finish(false)"><RotateCcw class="size-4" /> {{ t('hifz.weak') }}</button>
      </div>
    </section>

    <template v-else>
      <p v-if="failed" role="alert" class="mt-6 rounded-[var(--radius-tile)] bg-paper p-4 text-sm text-ink-soft shadow-soft">{{ t('daily.error') }}</p>

      <!-- Due today -->
      <section class="mt-6 rounded-[var(--radius-tile)] bg-gradient-to-br from-teal-950 via-teal-900 to-teal-700 p-6 text-cream shadow-lift">
        <p class="flex items-center gap-2 text-sm text-gold-200"><CalendarClock class="size-4" /> {{ t('hifz.dueToday') }}</p>
        <p v-if="!hifz.list.length" class="mt-2 text-cream/90">{{ t('hifz.start') }}</p>
        <p v-else-if="!due.length" class="mt-2 font-display text-2xl text-white">{{ t('hifz.allDone') }}</p>
        <div v-else class="mt-3 flex flex-wrap gap-2">
          <button v-for="h in due" :key="h.surah" type="button" :disabled="loading"
            class="rounded-full bg-gold-500 px-4 py-2 text-sm font-medium text-emerald-950 disabled:opacity-60" @click="review(h.surah)">{{ name(h.surah) }}</button>
        </div>
      </section>

      <!-- My surahs -->
      <section class="mt-6 rounded-[var(--radius-tile)] bg-paper p-5 shadow-soft">
        <h2 class="font-display text-lg text-emerald-900">{{ t('hifz.mine') }}</h2>
        <form class="mt-3 flex flex-wrap gap-2" @submit.prevent="addSurah(pick)">
          <select v-model.number="pick" :aria-label="t('hifz.choose')" class="min-w-0 flex-1 rounded-full border-line bg-cream py-2 pe-9 ps-4 text-sm">
            <option v-for="s in surahs" :key="s.id" :value="s.id">{{ s.id }}. {{ s.name_simple }}</option>
          </select>
          <button type="submit" class="inline-flex items-center gap-1.5 rounded-full border border-line px-4 py-2 text-sm text-emerald-900 hover:border-gold-500"><Plus class="size-4" /> {{ t('hifz.add') }}</button>
        </form>
        <ul class="mt-3 divide-y divide-line">
          <li v-for="h in sorted" :key="h.surah" class="flex flex-wrap items-center gap-3 py-3">
            <span class="min-w-0 flex-1">
              <span class="block text-ink">{{ name(h.surah) }}</span>
              <span class="text-xs" :class="isDue(h) ? 'text-gold-600' : 'text-ink-soft'">{{ isDue(h) ? t('hifz.due') : t('hifz.nextOn', { d: fmtDay(h.next) }) }}</span>
            </span>
            <button type="button" :disabled="loading" class="rounded-full border border-line px-3 py-1.5 text-sm text-emerald-900 hover:border-gold-500" @click="review(h.surah)">{{ t('hifz.review') }}</button>
            <button type="button" class="grid size-8 place-items-center rounded-full text-ink-soft hover:bg-cream" :aria-label="t('hifz.remove')" @click="removeSurah(h.surah)"><Trash2 class="size-4" /></button>
          </li>
        </ul>
      </section>
    </template>
  </QurbaShell>
</template>
