<!-- ===== QURBA: reminder settings ===== -->
<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Bell, BellOff } from 'lucide-vue-next';
import { disableReminders, enableReminders, rem, scheduleReminders, support } from '../lib/reminders';

const { t } = useI18n();
const status = ref(support());
const error = ref('');
const busy = ref(false);
const PR = ['fajr', 'dhuhr', 'asr', 'maghrib', 'isha'];

async function enable() { busy.value = true; error.value = await enableReminders(); status.value = support(); busy.value = false; }
async function disable() { await disableReminders(); }
let tm: number | undefined;
watch(() => [JSON.stringify(rem.prayers), rem.offset, JSON.stringify(rem.morning), JSON.stringify(rem.evening)], () => {
  clearTimeout(tm); tm = window.setTimeout(() => scheduleReminders(true), 800);
});
const msg = computed(() => error.value || (status.value !== 'ok' && !rem.enabled ? status.value : ''));
</script>

<template>
  <section class="rounded-[var(--radius-tile)] border border-line bg-paper p-5 text-sm">
    <div class="flex items-center gap-3">
      <Bell v-if="rem.enabled" class="size-5 text-emerald-700" /><BellOff v-else class="size-5 text-ink-soft" />
      <h2 class="flex-1 font-medium text-ink">{{ t('reminders.title') }}</h2>
      <button v-if="!rem.enabled" class="rounded-full bg-emerald-900 px-4 py-1.5 text-cream disabled:opacity-50" :disabled="busy" @click="enable">{{ t('reminders.turnOn') }}</button>
      <button v-else class="rounded-full border border-line px-4 py-1.5" @click="disable">{{ t('reminders.turnOff') }}</button>
    </div>
    <p v-if="msg" class="mt-3 rounded-xl bg-gold-200/50 px-3 py-2 text-xs text-ink">{{ t('reminders.err_' + msg) }}</p>

    <div v-if="rem.enabled" class="mt-4 space-y-4">
      <div class="flex flex-wrap gap-2">
        <label v-for="p in PR" :key="p" class="flex items-center gap-2 rounded-full border border-line px-3 py-1.5">
          <input v-model="rem.prayers[p]" type="checkbox" class="rounded text-emerald-900 focus:ring-gold-500" /> {{ t('prayer.' + p) }}
        </label>
      </div>
      <label class="flex items-center gap-3">
        <span class="text-ink-soft">{{ t('reminders.when') }}</span>
        <select v-model.number="rem.offset" class="rounded-full border-line py-1 pe-8 text-sm">
          <option :value="0">{{ t('reminders.atTime') }}</option>
          <option v-for="m in [5, 10, 15, 30]" :key="m" :value="m">{{ t('reminders.before', { n: m }) }}</option>
        </select>
      </label>
      <div class="grid gap-3 sm:grid-cols-2">
        <label class="flex items-center gap-3"><input v-model="rem.morning.on" type="checkbox" class="rounded text-emerald-900 focus:ring-gold-500" />
          {{ t('reminders.morningTitle') }} <input v-model="rem.morning.time" type="time" class="ms-auto rounded-full border-line py-1 text-sm" :disabled="!rem.morning.on" /></label>
        <label class="flex items-center gap-3"><input v-model="rem.evening.on" type="checkbox" class="rounded text-emerald-900 focus:ring-gold-500" />
          {{ t('reminders.eveningTitle') }} <input v-model="rem.evening.time" type="time" class="ms-auto rounded-full border-line py-1 text-sm" :disabled="!rem.evening.on" /></label>
      </div>
      <p class="text-xs text-ink-soft">{{ t('reminders.note') }}</p>
    </div>
  </section>
</template>
