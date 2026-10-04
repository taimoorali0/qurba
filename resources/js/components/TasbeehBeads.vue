<!-- ===== QURBA: Tasbeeh bead ring ===== -->
<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{ count: number; target: number }>();

const CX = 130, CY = 122, R = 104, GAP = 44; // degrees left open at the bottom for the imam bead
const N = computed(() => Math.max(1, Math.min(props.target, 33)));       // beads on the string
const filled = computed(() => (props.count === 0 ? 0 : ((props.count - 1) % N.value) + 1));
const lap = computed(() => (props.count === 0 ? 0 : Math.floor((props.count - 1) / N.value) + 1));
const laps = computed(() => Math.ceil(props.target / N.value));
const beadR = computed(() => Math.min(10, ((2 * Math.PI * R * (360 - GAP)) / 360 / N.value) * 0.42));

const beads = computed(() => Array.from({ length: N.value }, (_, i) => {
  const step = N.value > 1 ? (360 - GAP) / (N.value - 1) : 0;
  const a = ((90 + GAP / 2 + i * step) * Math.PI) / 180; // start bottom-left, go clockwise up and round
  return { x: CX + R * Math.cos(a), y: CY + R * Math.sin(a), on: i < filled.value, current: i === filled.value - 1 };
}));

defineExpose({ lap, laps });
</script>

<template>
  <svg viewBox="0 0 260 280" class="absolute inset-0 size-full" aria-hidden="true">
    <defs>
      <radialGradient id="qb-on" cx="35%" cy="30%" r="70%">
        <stop offset="0%" stop-color="#3FA27F" /><stop offset="55%" stop-color="#14654D" /><stop offset="100%" stop-color="#072A20" />
      </radialGradient>
      <radialGradient id="qb-cur" cx="35%" cy="30%" r="70%">
        <stop offset="0%" stop-color="#FBEBC0" /><stop offset="50%" stop-color="#C9A24A" /><stop offset="100%" stop-color="#7A5B1C" />
      </radialGradient>
      <radialGradient id="qb-off" cx="35%" cy="30%" r="70%">
        <stop offset="0%" stop-color="#FFFFFF" /><stop offset="100%" stop-color="#E7E1D2" />
      </radialGradient>
    </defs>

    <!-- string -->
    <circle :cx="CX" :cy="CY" :r="R" fill="none" stroke="#C9A24A" stroke-width="1.2" opacity=".55" />

    <!-- beads -->
    <g v-for="(b, i) in beads" :key="i">
      <ellipse :cx="b.x + 1.2" :cy="b.y + 2" :rx="beadR" :ry="beadR * 0.9" fill="#1E2925" opacity=".12" />
      <circle :cx="b.x" :cy="b.y" :r="b.current ? beadR * 1.18 : beadR"
        :fill="b.current ? 'url(#qb-cur)' : b.on ? 'url(#qb-on)' : 'url(#qb-off)'"
        :stroke="b.on || b.current ? 'none' : '#D9D1BD'" stroke-width="1"
        class="transition-[r] duration-150" />
      <circle :cx="b.x - beadR * 0.35" :cy="b.y - beadR * 0.4" :r="beadR * 0.28" fill="#FFFFFF" :opacity="b.on || b.current ? 0.45 : 0.8" />
    </g>

    <!-- imam bead + tassel -->
    <g :transform="`translate(${CX} ${CY + R})`">
      <path d="M0 2 C -9 2 -10 14 -6 22 C -4 27 -5 34 -3 40 L 3 40 C 5 34 4 27 6 22 C 10 14 9 2 0 2 Z" fill="url(#qb-on)" />
      <rect x="-5" y="21" width="10" height="3" rx="1.5" fill="#C9A24A" />
      <path d="M0 40 C 0 50 6 54 18 52" fill="none" stroke="#C9A24A" stroke-width="2" stroke-linecap="round" />
      <circle cx="22" cy="51" r="6" fill="url(#qb-on)" />
      <circle cx="20" cy="49" r="1.8" fill="#FFFFFF" opacity=".45" />
    </g>
  </svg>
</template>
