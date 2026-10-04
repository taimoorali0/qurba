<!-- ===== QURBA: Prayer times ===== -->
<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { ChevronLeft, ChevronRight, Moon, Sun, Sunrise, Sunset, CloudSun, Settings2 } from 'lucide-vue-next';
import QurbaShell from '../layouts/QurbaShell.vue';
import LocationPicker from '../components/LocationPicker.vue';
import RemindersCard from '../components/RemindersCard.vue';
import { loc } from '../lib/location';
import { countdown, effective, fmtTime, METHODS, nextPrayer, PRAYERS, prayerSettings, timesFor, type MethodKey } from '../lib/prayer';

const { t, locale } = useI18n();
const now = ref(new Date());
let timer: number | undefined;
onMounted(() => { timer = window.setInterval(() => (now.value = new Date()), 30000); });
onBeforeUnmount(() => clearInterval(timer));

const offset = ref(0); // days from today
const day = computed(() => { const d = new Date(now.value); d.setDate(d.getDate() + offset.value); return d; });
const times = computed(() => (loc.place ? timesFor(loc.place, day.value) : []));
const next = computed(() => (loc.place ? nextPrayer(loc.place, now.value) : null));
const eff = computed(() => (loc.place ? effective(loc.place) : null));
const showSettings = ref(false);
const icons: Record<string, any> = { fajr: Moon, sunrise: Sunrise, dhuhr: Sun, asr: CloudSun, maghrib: Sunset, isha: Moon };
const tz = computed(() => loc.place?.tz ?? Intl.DateTimeFormat().resolvedOptions().timeZone);
const dateLabel = computed(() => day.value.toLocaleDateString(locale.value, { weekday: 'long', day: 'numeric', month: 'long', timeZone: tz.value }));
const hijri = computed(() => { try { return day.value.toLocaleDateString(locale.value + '-u-ca-islamic-umalqura', { day: 'numeric', month: 'long', year: 'numeric', timeZone: tz.value }); } catch { return ''; } });
const isNext = (name: string, time: Date) => offset.value === 0 && next.value?.name === name && next.value.time.getTime() === time.getTime();
</script>

<template>
  <Head :title="t('nav.prayer')" />
  <QurbaShell>
    <div class="flex items-center justify-between gap-3">
      <h1 class="font-display text-3xl text-emerald-900 md:text-4xl">{{ t('nav.prayer') }}</h1>
      <button v-if="loc.place" class="grid size-9 place-items-center rounded-full border border-line bg-paper" @click="showSettings = !showSettings" :aria-label="t('prayer.settings')"><Settings2 class="size-4" /></button>
    </div>

    <div class="mt-5"><LocationPicker /></div>

    <template v-if="loc.place">
      <!-- Settings -->
      <section v-if="showSettings && eff" class="mt-5 grid gap-4 rounded-[var(--radius-tile)] border border-line bg-paper p-5 text-sm md:grid-cols-2">
        <label class="block">
          <span class="text-xs text-ink-soft">{{ t('prayer.method') }}</span>
          <select v-model="prayerSettings.method" class="mt-1 w-full rounded-full border-line py-1.5 text-sm">
            <option value="">{{ t('prayer.auto', { m: METHODS[eff.method as MethodKey] }) }}</option>
            <option v-for="(name, key) in METHODS" :key="key" :value="key">{{ name }}</option>
          </select>
        </label>
        <label class="block">
          <span class="text-xs text-ink-soft">{{ t('prayer.asrMethod') }}</span>
          <select v-model="prayerSettings.asr" class="mt-1 w-full rounded-full border-line py-1.5 text-sm">
            <option value="">{{ t('prayer.auto', { m: t('prayer.asr_' + eff.asr) }) }}</option>
            <option value="standard">{{ t('prayer.asr_standard') }}</option>
            <option value="hanafi">{{ t('prayer.asr_hanafi') }}</option>
          </select>
        </label>
        <div class="md:col-span-2">
          <p class="text-xs text-ink-soft">{{ t('prayer.adjustments') }}</p>
          <div class="mt-2 grid grid-cols-3 gap-3 sm:grid-cols-6">
            <label v-for="p in PRAYERS" :key="p" class="block text-center">
              <span class="text-xs">{{ t('prayer.' + p) }}</span>
              <input v-model.number="prayerSettings.adjust[p]" type="number" min="-30" max="30" class="mt-1 w-full rounded-full border-line py-1 text-center text-sm" />
            </label>
          </div>
        </div>
      </section>

      <div class="mt-6 grid gap-6 lg:grid-cols-[1.5fr_1fr] lg:items-start">
      <div>
      <!-- Day -->
      <div class="lg:hidden">      <!-- Next prayer -->
      <div v-if="next" class="mb-6 rounded-[var(--radius-sheet)] bg-emerald-900 p-6 text-cream">
        <p class="text-sm text-gold-200">{{ t('home.nextPrayer') }}</p>
        <p class="mt-1 font-display text-4xl">{{ t('prayer.' + next.name) }} <span class="text-gold-500">{{ fmtTime(next.time, tz, locale) }}</span></p>
        <p class="mt-1 text-sm text-gold-200">{{ t('prayer.in', { t: countdown(next.time, now) }) }}</p>
      </div>

</div>
      <div class="flex items-center justify-between gap-3">
        <button class="grid size-9 place-items-center rounded-full border border-line bg-paper" @click="offset--" :aria-label="t('prayer.prevDay')"><ChevronLeft class="size-4 rtl:rotate-180" /></button>
        <div class="text-center">
          <p class="font-medium text-ink">{{ dateLabel }}</p>
          <p class="text-xs text-ink-soft">{{ hijri }}</p>
        </div>
        <button class="grid size-9 place-items-center rounded-full border border-line bg-paper" @click="offset++" :aria-label="t('prayer.nextDay')"><ChevronRight class="size-4 rtl:rotate-180" /></button>
      </div>
      <button v-if="offset !== 0" class="mx-auto mt-2 block text-xs text-emerald-700 underline" @click="offset = 0">{{ t('prayer.today') }}</button>

      <ul class="mt-4 divide-y divide-line overflow-hidden rounded-[var(--radius-tile)] border border-line bg-paper">
        <li v-for="p in times" :key="p.name" class="flex items-center gap-4 px-5 py-4" :class="isNext(p.name, p.time) ? 'bg-emerald-100' : ''">
          <component :is="icons[p.name]" class="size-5" :class="p.name === 'sunrise' ? 'text-gold-600' : 'text-emerald-700'" />
          <span class="flex-1" :class="p.name === 'sunrise' ? 'text-ink-soft' : 'font-medium text-ink'">{{ t('prayer.' + p.name) }}</span>
          <span v-if="p.adjusted" class="rounded-full bg-gold-200 px-2 py-0.5 text-[11px] text-ink">{{ t('prayer.adjusted', { n: (prayerSettings.adjust[p.name] > 0 ? '+' : '') + prayerSettings.adjust[p.name] }) }}</span>
          <span class="tabular-nums text-ink">{{ fmtTime(p.time, tz, locale) }}</span>
        </li>
      </ul>
      </div>

      <aside class="space-y-4">
      <div class="hidden lg:block">      <!-- Next prayer -->
      <div v-if="next" class="rounded-[var(--radius-sheet)] bg-emerald-900 p-6 text-cream">
        <p class="text-sm text-gold-200">{{ t('home.nextPrayer') }}</p>
        <p class="mt-1 font-display text-4xl">{{ t('prayer.' + next.name) }} <span class="text-gold-500">{{ fmtTime(next.time, tz, locale) }}</span></p>
        <p class="mt-1 text-sm text-gold-200">{{ t('prayer.in', { t: countdown(next.time, now) }) }}</p>
      </div>

</div>
      <RemindersCard />

      <p v-if="eff" class="text-xs text-ink-soft">
        {{ t('prayer.calcNote', { m: METHODS[eff.method as MethodKey], a: t('prayer.asr_' + eff.asr) }) }}
      </p>
      </aside>
      </div>
    </template>
  </QurbaShell>
</template>
