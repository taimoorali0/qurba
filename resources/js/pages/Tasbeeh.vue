<!-- ===== QURBA: Tasbeeh ===== -->
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { ChevronLeft, Plus, RotateCcw, Trash2, Undo2, Vibrate, Volume2, VolumeX } from 'lucide-vue-next';
import QurbaShell from '../layouts/QurbaShell.vue';
import TasbeehBeads from '../components/TasbeehBeads.vue';
import { activeType, addCustom, allTypes, canVibrate, lastDays, removeCustom, reset, selectZikr, setTarget, tap, targetOf, tb, todayTotal, undo, type ZikrType } from '../lib/tasbeeh';

const { t, locale } = useI18n();
const tab = ref<'counter' | 'history' | 'goals'>('counter');
const zikr = computed(() => activeType());
const target = computed(() => targetOf(zikr.value));
const label = (z: ZikrType) => z.label[locale.value] ?? z.label.en;
const done = computed(() => tb.count >= target.value);

const pulse = ref(false);
function onTap() {
  tap();
  pulse.value = false; requestAnimationFrame(() => (pulse.value = true));
}

// Reset confirmation
const confirmReset = ref(false);
function doReset() { reset(); confirmReset.value = false; }

// Target editing
const customTarget = ref<number | null>(null);
function applyTarget(n: number) { setTarget(zikr.value.id, n); customTarget.value = null; }

// Custom zikr
const newText = ref(''); const newTarget = ref(33); const adding = ref(false);
function saveCustom() { addCustom(newText.value, newTarget.value); newText.value = ''; adding.value = false; }

// Keyboard: Space / Enter counts, Backspace undoes
function onKey(e: KeyboardEvent) {
  if (tab.value !== 'counter' || (e.target as HTMLElement)?.closest('input,select,textarea,button')) return;
  if (e.code === 'Space' || e.code === 'Enter') { e.preventDefault(); onTap(); }
  if (e.code === 'Backspace') { e.preventDefault(); undo(); }
}
onMounted(() => window.addEventListener('keydown', onKey));
onBeforeUnmount(() => window.removeEventListener('keydown', onKey));

const week = computed(() => lastDays(7));
const weekMax = computed(() => Math.max(1, ...week.value.map((d) => d.total)));
const typeName = (id: string) => { const z = allTypes().find((x) => x.id === id); return z ? label(z) : '—'; };
const fmt = (ms: number) => new Date(ms).toLocaleString(locale.value, { dateStyle: 'medium', timeStyle: 'short' });
const dayName = (d: Date) => d.toLocaleDateString(locale.value, { weekday: 'short' });
const goalPct = computed(() => (tb.dailyGoal ? Math.min(1, todayTotal() / tb.dailyGoal) : 0));
</script>

<template>
  <Head :title="t('home.tasbeeh')" />
  <QurbaShell>
    <div class="flex items-center gap-3">
      <Link href="/zikr" class="grid size-9 place-items-center rounded-full border border-line bg-paper" :aria-label="t('nav.zikr')"><ChevronLeft class="size-4 rtl:rotate-180" /></Link>
      <h1 class="font-display text-3xl text-emerald-900">{{ t('home.tasbeeh') }}</h1>
    </div>

    <div role="tablist" class="mt-5 grid grid-cols-3 rounded-full border border-line bg-paper p-1 text-sm sm:inline-grid">
      <button v-for="k in (['counter', 'history', 'goals'] as const)" :key="k" role="tab" :aria-selected="tab === k"
        class="rounded-full px-5 py-1.5" :class="tab === k ? 'bg-emerald-900 text-cream' : 'text-ink-soft'" @click="tab = k">{{ t('tasbeeh.' + k) }}</button>
    </div>

    <!-- COUNTER -->
    <section v-if="tab === 'counter'" class="mt-6 grid gap-6 lg:grid-cols-[1fr_20rem]">
      <div class="flex flex-col items-center">
        <!-- The whole ring is the tap area -->
        <button type="button" @click="onTap" :aria-label="`${label(zikr)} — ${tb.count}`"
          class="relative grid aspect-[260/280] w-full max-w-[24rem] select-none place-items-center rounded-full outline-none [touch-action:manipulation] [-webkit-tap-highlight-color:transparent] active:scale-[0.985] transition-transform">
          <TasbeehBeads :count="tb.count" :target="target" />
          <span class="relative -mt-6 text-center">
            <span :key="tb.count" class="block font-display text-6xl text-emerald-900 tabular-nums" :class="pulse ? 'animate-[qtap_.18s_ease-out]' : ''">{{ tb.count }}</span>
            <span dir="rtl" lang="ar" class="mt-2 block font-quran text-2xl text-emerald-900">{{ zikr.ar }}</span>
            <span class="mt-1 block text-xs text-ink-soft">{{ done ? t('tasbeeh.completed') : t('tasbeeh.of', { n: target }) }}</span>
            <span v-if="target > 33 && tb.count" class="mt-0.5 block text-[11px] text-gold-600">{{ t('tasbeeh.round', { n: Math.ceil(tb.count / 33), t: Math.ceil(target / 33) }) }}</span>
          </span>
        </button>
        <p class="mt-3 text-xs text-ink-soft">{{ t('tasbeeh.tapHint') }}</p>

        <div class="mt-4 flex items-center gap-3">
          <button class="inline-flex items-center gap-2 rounded-full border border-line bg-paper px-4 py-2 text-sm disabled:opacity-40" :disabled="!tb.count" @click="undo"><Undo2 class="size-4" /> {{ t('tasbeeh.undo') }}</button>
          <button class="inline-flex items-center gap-2 rounded-full border border-line bg-paper px-4 py-2 text-sm disabled:opacity-40" :disabled="!tb.count" @click="confirmReset = true"><RotateCcw class="size-4" /> {{ t('tasbeeh.reset') }}</button>
        </div>
        <div v-if="confirmReset" role="alertdialog" class="mt-3 flex items-center gap-3 rounded-2xl border border-gold-500 bg-paper px-4 py-3 text-sm">
          {{ t('tasbeeh.resetConfirm', { n: tb.count }) }}
          <button class="rounded-full bg-emerald-900 px-4 py-1.5 text-cream" @click="doReset">{{ t('tasbeeh.reset') }}</button>
          <button class="rounded-full px-3 py-1.5 text-ink-soft" @click="confirmReset = false">{{ t('tasbeeh.cancel') }}</button>
        </div>
      </div>

      <aside class="space-y-4">
        <!-- Feedback toggles -->
        <div class="rounded-[var(--radius-tile)] border border-line bg-paper p-4 text-sm">
          <label class="flex items-center justify-between gap-3">
            <span class="flex items-center gap-2"><Volume2 v-if="tb.sound" class="size-4" /><VolumeX v-else class="size-4" /> {{ t('tasbeeh.sound') }}</span>
            <input v-model="tb.sound" type="checkbox" class="rounded text-emerald-900 focus:ring-gold-500" />
          </label>
          <label class="mt-3 flex items-center justify-between gap-3" :class="!canVibrate ? 'opacity-50' : ''">
            <span class="flex items-center gap-2"><Vibrate class="size-4" /> {{ t('tasbeeh.vibration') }}</span>
            <input v-model="tb.vibrate" type="checkbox" :disabled="!canVibrate" class="rounded text-emerald-900 focus:ring-gold-500" />
          </label>
          <p v-if="!canVibrate" class="mt-2 text-xs text-ink-soft">{{ t('tasbeeh.noVibration') }}</p>
        </div>

        <!-- Target -->
        <div class="rounded-[var(--radius-tile)] border border-line bg-paper p-4 text-sm">
          <p class="text-ink-soft">{{ t('tasbeeh.target') }}</p>
          <div class="mt-2 flex flex-wrap gap-2">
            <button v-for="n in [33, 100]" :key="n" class="rounded-full px-4 py-1.5" :class="target === n ? 'bg-emerald-900 text-cream' : 'border border-line'" @click="applyTarget(n)">{{ n }}</button>
            <input v-model.number="customTarget" type="number" min="1" :placeholder="t('tasbeeh.custom')" class="w-24 rounded-full border-line py-1.5 text-sm" @keyup.enter="customTarget && applyTarget(customTarget)" />
            <button v-if="customTarget" class="rounded-full bg-gold-500 px-3 py-1.5 text-emerald-950" @click="applyTarget(customTarget)">{{ t('tasbeeh.set') }}</button>
          </div>
        </div>
      </aside>

      <!-- Zikr chooser -->
      <div class="lg:col-span-2">
        <ul class="grid grid-cols-2 gap-3 sm:grid-cols-4">
          <li v-for="z in allTypes()" :key="z.id" class="relative">
            <button class="w-full rounded-[var(--radius-tile)] border px-3 py-3 text-center text-sm"
              :class="z.id === tb.activeId ? 'border-emerald-900 bg-emerald-900 text-cream' : 'border-line bg-paper'" @click="selectZikr(z.id)">
              <span class="block truncate">{{ label(z) }}</span>
              <span class="mt-1 block text-xs opacity-70">{{ targetOf(z) }}</span>
            </button>
            <button v-if="!z.builtin" class="absolute -end-1 -top-1 grid size-6 place-items-center rounded-full bg-paper text-ink-soft shadow" @click="removeCustom(z.id)" :aria-label="t('tasbeeh.remove')"><Trash2 class="size-3" /></button>
          </li>
          <li>
            <button class="flex h-full w-full items-center justify-center gap-2 rounded-[var(--radius-tile)] border border-dashed border-gold-500 px-3 py-3 text-sm text-gold-600" @click="adding = !adding"><Plus class="size-4" /> {{ t('tasbeeh.addCustom') }}</button>
          </li>
        </ul>
        <div v-if="adding" class="mt-3 flex flex-wrap items-center gap-2 rounded-[var(--radius-tile)] border border-line bg-paper p-3 text-sm">
          <input v-model="newText" :placeholder="t('tasbeeh.customText')" class="min-w-0 flex-1 rounded-full border-line py-1.5" />
          <input v-model.number="newTarget" type="number" min="1" class="w-24 rounded-full border-line py-1.5" />
          <button class="rounded-full bg-emerald-900 px-4 py-1.5 text-cream disabled:opacity-40" :disabled="!newText.trim()" @click="saveCustom">{{ t('tasbeeh.save') }}</button>
        </div>
      </div>
    </section>

    <!-- HISTORY -->
    <section v-else-if="tab === 'history'" class="mt-6 space-y-6">
      <div class="rounded-[var(--radius-tile)] border border-line bg-paper p-5">
        <p class="text-sm text-ink-soft">{{ t('tasbeeh.last7') }}</p>
        <div class="mt-4 flex h-32 items-end gap-3">
          <div v-for="d in week" :key="d.key" class="flex flex-1 flex-col items-center gap-1">
            <span class="text-[11px] text-ink-soft">{{ d.total || '' }}</span>
            <div class="w-full rounded-t-lg bg-gold-500" :style="{ height: Math.max(2, (d.total / weekMax) * 96) + 'px' }" />
            <span class="text-[11px] text-ink-soft">{{ dayName(d.day) }}</span>
          </div>
        </div>
      </div>
      <ul class="divide-y divide-line rounded-[var(--radius-tile)] border border-line bg-paper">
        <li v-if="!tb.history.length" class="p-5 text-sm text-ink-soft">{{ t('tasbeeh.noHistory') }}</li>
        <li v-for="s in tb.history.slice(0, 50)" :key="s.id" class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
          <span><span class="block">{{ typeName(s.typeId) }}</span><span class="text-xs text-ink-soft">{{ fmt(s.end) }}</span></span>
          <span class="tabular-nums" :class="s.count >= s.target ? 'text-emerald-900' : 'text-ink-soft'">{{ s.count }} / {{ s.target }}</span>
        </li>
      </ul>
    </section>

    <!-- GOALS -->
    <section v-else class="mt-6 max-w-md rounded-[var(--radius-tile)] border border-line bg-paper p-5 text-sm">
      <label class="block text-ink-soft" for="goal">{{ t('tasbeeh.dailyGoal') }}</label>
      <input id="goal" v-model.number="tb.dailyGoal" type="number" min="0" step="1" class="mt-2 w-32 rounded-full border-line py-1.5" />
      <p class="mt-1 text-xs text-ink-soft">{{ t('tasbeeh.goalHint') }}</p>
      <template v-if="tb.dailyGoal">
        <div class="mt-5 h-2 rounded-full bg-line"><div class="h-2 rounded-full bg-emerald-900" :style="{ width: goalPct * 100 + '%' }" /></div>
        <p class="mt-2">{{ t('tasbeeh.goalProgress', { n: todayTotal(), g: tb.dailyGoal }) }}</p>
      </template>
    </section>
  </QurbaShell>
</template>

<style>
@keyframes qtap { from { transform: scale(1.08); } to { transform: scale(1); } }
</style>
