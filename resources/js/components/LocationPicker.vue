<!-- ===== QURBA: location picker (device or manual) ===== -->
<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { LocateFixed, MapPin } from 'lucide-vue-next';
import { CITIES, loc, setCity, setManual, useDeviceLocation } from '../lib/location';

const { t } = useI18n();
const showManual = ref(!loc.place);
const lat = ref<number | null>(null), lng = ref<number | null>(null);
const coordErr = ref(false);
function saveCoords() { coordErr.value = !(lat.value != null && lng.value != null && setManual(lat.value, lng.value)); if (!coordErr.value) showManual.value = false; }
async function useDevice() { if (await useDeviceLocation()) showManual.value = false; }
function pickCity(e: Event) { const c = CITIES.find((x) => x.label === (e.target as HTMLSelectElement).value); if (c) { setCity(c); showManual.value = false; } }
</script>

<template>
  <div class="rounded-[var(--radius-tile)] bg-paper shadow-soft p-4 text-sm">
    <div class="flex flex-wrap items-center gap-3">
      <MapPin class="size-4 text-emerald-700" />
      <span v-if="loc.place" class="min-w-[10rem] flex-1">
        <span class="block truncate font-medium text-ink">{{ loc.place.label }}</span>
        <span class="text-xs text-ink-soft">{{ loc.place.mode === 'device' ? t('loc.device') : t('loc.manual') }} · {{ loc.place.tz }}</span>
      </span>
      <span v-else class="min-w-[10rem] flex-1 text-ink-soft">{{ t('loc.none') }}</span>
      <button class="inline-flex items-center gap-2 rounded-full bg-emerald-900 px-4 py-1.5 text-cream disabled:opacity-50" :disabled="loc.busy" @click="useDevice">
        <LocateFixed class="size-4" /> {{ loc.busy ? t('loc.locating') : t('loc.useDevice') }}
      </button>
      <button v-if="loc.place" class="rounded-full border border-line px-4 py-1.5" @click="showManual = !showManual">{{ t('loc.change') }}</button>
    </div>
    <p v-if="loc.error" class="mt-3 rounded-xl bg-gold-200/50 px-3 py-2 text-xs text-ink">{{ t('loc.err_' + loc.error) }}</p>

    <div v-if="showManual" class="mt-4 grid gap-3 border-t border-line pt-4 sm:grid-cols-2">
      <label class="block">
        <span class="text-xs text-ink-soft">{{ t('loc.city') }}</span>
        <select class="mt-1 w-full rounded-full border-line py-1.5 text-sm" @change="pickCity">
          <option value="">{{ t('loc.chooseCity') }}</option>
          <option v-for="c in CITIES" :key="c.label" :value="c.label">{{ c.label }}</option>
        </select>
      </label>
      <div>
        <span class="text-xs text-ink-soft">{{ t('loc.coords') }}</span>
        <div class="mt-1 flex gap-2">
          <input v-model.number="lat" type="number" step="0.0001" :placeholder="t('loc.lat')" :aria-label="t('loc.lat')" class="w-full rounded-full border-line py-1.5 text-sm" />
          <input v-model.number="lng" type="number" step="0.0001" :placeholder="t('loc.lng')" :aria-label="t('loc.lng')" class="w-full rounded-full border-line py-1.5 text-sm" />
          <button class="rounded-full bg-gold-500 px-3 text-emerald-950" @click="saveCoords">{{ t('loc.set') }}</button>
        </div>
        <p v-if="coordErr" class="mt-1 text-xs text-red-700">{{ t('loc.badCoords') }}</p>
      </div>
    </div>
  </div>
</template>
