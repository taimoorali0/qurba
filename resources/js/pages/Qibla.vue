<!-- ===== QURBA: Qibla compass ===== -->
<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Compass, Smartphone } from 'lucide-vue-next';
import { Coordinates, Qibla } from 'adhan';
import QurbaShell from '../layouts/QurbaShell.vue';
import PageTitle from '../components/PageTitle.vue';
import LocationPicker from '../components/LocationPicker.vue';
import { loc } from '../lib/location';

const { t } = useI18n();
const KAABA = { lat: 21.4225, lng: 39.8262 };

const bearing = computed(() => (loc.place ? Qibla(new Coordinates(loc.place.lat, loc.place.lng)) : null));
const distanceKm = computed(() => {
  if (!loc.place) return null;
  const r = Math.PI / 180, R = 6371;
  const dLat = (KAABA.lat - loc.place.lat) * r, dLng = (KAABA.lng - loc.place.lng) * r;
  const a = Math.sin(dLat / 2) ** 2 + Math.cos(loc.place.lat * r) * Math.cos(KAABA.lat * r) * Math.sin(dLng / 2) ** 2;
  return Math.round(2 * R * Math.asin(Math.sqrt(a)));
});

// ---- Compass sensor ----
type State = 'idle' | 'waiting' | 'live' | 'unavailable' | 'denied';
const state = ref<State>('idle');
const heading = ref<number | null>(null);
const accuracy = ref<number | null>(null); // degrees (iOS only)
let timeout: number | undefined;
let listening = '';

function screenAngle() { return (screen.orientation?.angle ?? (window as any).orientation ?? 0) as number; }

function onOrient(e: DeviceOrientationEvent & { webkitCompassHeading?: number; webkitCompassAccuracy?: number }) {
  let h: number | null = null;
  if (typeof e.webkitCompassHeading === 'number') {           // iOS: true heading
    h = e.webkitCompassHeading;
    accuracy.value = typeof e.webkitCompassAccuracy === 'number' ? e.webkitCompassAccuracy : null;
  } else if (e.absolute && e.alpha != null) {                  // Android / Chrome: absolute orientation
    h = (360 - e.alpha + screenAngle()) % 360;
    accuracy.value = null;
  }
  if (h == null) return;
  clearTimeout(timeout);
  heading.value = h;
  state.value = 'live';
}

async function startCompass() {
  const DOE: any = (window as any).DeviceOrientationEvent;
  if (!DOE || !window.isSecureContext) { state.value = 'unavailable'; return; }
  if (typeof DOE.requestPermission === 'function') {          // iOS 13+: must be from a tap
    try { if ((await DOE.requestPermission()) !== 'granted') { state.value = 'denied'; return; } }
    catch { state.value = 'denied'; return; }
  }
  state.value = 'waiting';
  listening = 'ondeviceorientationabsolute' in window ? 'deviceorientationabsolute' : 'deviceorientation';
  window.addEventListener(listening, onOrient as EventListener, true);
  timeout = window.setTimeout(() => { if (state.value === 'waiting') state.value = 'unavailable'; }, 3000);
}
onBeforeUnmount(() => { if (listening) window.removeEventListener(listening, onOrient as EventListener, true); clearTimeout(timeout); });

const diff = computed(() => (bearing.value != null && heading.value != null ? ((bearing.value - heading.value + 540) % 360) - 180 : null));
const facing = computed(() => diff.value != null && Math.abs(diff.value) <= 5);
const needsCalibration = computed(() => accuracy.value != null && (accuracy.value < 0 || accuracy.value > 25));
let buzzed = false;
const _ = computed(() => { if (facing.value && !buzzed) { buzzed = true; navigator.vibrate?.(60); } if (!facing.value) buzzed = false; return facing.value; });

// Rotation: live = dial follows the phone; static = North up, Qibla needle at its bearing
const dialRotation = computed(() => (state.value === 'live' && heading.value != null ? -heading.value : 0));
const deg = (n: number) => `${Math.round(n)}°`;
</script>

<template>
  <Head :title="t('nav.qibla')" />
  <QurbaShell>
    <PageTitle :title="t('nav.qibla')" arabic="القبلة" class="flex-1" />
    <div class="mt-5"><LocationPicker /></div>

    <section v-if="loc.place && bearing != null" class="mt-6 flex flex-col items-center">
      <div class="relative aspect-square w-full max-w-[20rem]" :data-facing="_">
        <svg viewBox="0 0 240 240" class="size-full">
          <circle cx="120" cy="120" r="112" fill="#FFFFFF" :stroke="facing ? '#0B3B2D' : '#E7E1D2'" stroke-width="6" class="transition-colors" />
          <g :style="{ transform: `rotate(${dialRotation}deg)`, transformOrigin: '120px 120px', transition: state === 'live' ? 'transform .15s linear' : 'none' }">
            <g v-for="i in 72" :key="i">
              <line x1="120" y1="14" x2="120" :y2="i % 18 === 1 ? 26 : 20" stroke="#C9C1AD" stroke-width="1.5" :transform="`rotate(${(i - 1) * 5} 120 120)`" />
            </g>
            <text x="120" y="42" text-anchor="middle" font-size="14" fill="#B4402F" font-weight="600">N</text>
            <text x="200" y="125" text-anchor="middle" font-size="12" fill="#5B6862">E</text>
            <text x="120" y="206" text-anchor="middle" font-size="12" fill="#5B6862">S</text>
            <text x="40" y="125" text-anchor="middle" font-size="12" fill="#5B6862">W</text>
            <!-- Qibla needle -->
            <g :transform="`rotate(${bearing} 120 120)`">
              <line x1="120" y1="120" x2="120" y2="38" stroke="#C9A24A" stroke-width="4" stroke-linecap="round" />
              <rect x="108" y="22" width="24" height="24" rx="3" fill="#1E2925" />
              <rect x="108" y="30" width="24" height="3" fill="#C9A24A" />
            </g>
          </g>
          <!-- fixed top marker = direction the phone points -->
          <path d="M120 2 L128 16 L112 16 Z" :fill="state === 'live' ? '#0B3B2D' : 'transparent'" />
          <circle cx="120" cy="120" r="6" fill="#0B3B2D" />
        </svg>
      </div>

      <p class="mt-4 font-display text-4xl text-emerald-900">{{ deg(bearing) }}</p>
      <p class="text-sm text-ink-soft">{{ t('qibla.fromNorth') }} · {{ t('qibla.distance', { n: distanceKm?.toLocaleString() }) }}</p>

      <p v-if="state === 'live'" class="mt-4 rounded-full px-5 py-2 text-sm" :class="facing ? 'bg-emerald-900 text-cream' : 'bg-paper text-ink'">
        {{ facing ? t('qibla.facing') : t('qibla.turn', { n: deg(Math.abs(diff ?? 0)), dir: (diff ?? 0) > 0 ? t('qibla.right') : t('qibla.left') }) }}
      </p>

      <div class="mt-5 w-full max-w-md space-y-3 text-sm">
        <button v-if="state === 'idle'" class="flex w-full items-center justify-center gap-2 rounded-full bg-emerald-900 px-5 py-3 text-cream" @click="startCompass">
          <Compass class="size-4" /> {{ t('qibla.start') }}
        </button>
        <p v-if="state === 'waiting'" class="text-center text-ink-soft">{{ t('qibla.waiting') }}</p>
        <p v-if="state === 'unavailable'" class="rounded-xl bg-gold-200/50 px-4 py-3 text-ink">{{ t('qibla.unavailable') }}</p>
        <p v-if="state === 'denied'" class="rounded-xl bg-gold-200/50 px-4 py-3 text-ink">{{ t('qibla.denied') }}</p>
        <p v-if="state === 'live' && needsCalibration" class="flex items-start gap-2 rounded-xl bg-gold-200/50 px-4 py-3 text-ink"><Smartphone class="mt-0.5 size-4 shrink-0" /> {{ t('qibla.calibrate') }}</p>
        <p v-if="state === 'live'" class="text-center text-xs text-ink-soft">{{ t('qibla.tips') }}</p>
      </div>
    </section>
  </QurbaShell>
</template>
