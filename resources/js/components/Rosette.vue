<!-- ===== QURBA: original arabesque rosette (16 petals, scalloped rings, eight-point star) ===== -->
<script setup lang="ts">
const petals = Array.from({ length: 16 }, (_, i) => i * 22.5);
const inner = Array.from({ length: 12 }, (_, i) => i * 30);
const dots = Array.from({ length: 32 }, (_, i) => i * 11.25);
</script>

<template>
  <svg viewBox="0 0 400 400" aria-hidden="true" fill="none" stroke="currentColor" stroke-linecap="round">
    <!-- outer petals -->
    <g stroke-width="1.6">
      <path v-for="a in petals" :key="'p' + a" :transform="`rotate(${a} 200 200)`"
        d="M200 18 C232 60 236 100 200 132 C164 100 168 60 200 18 Z" />
      <path v-for="a in petals" :key="'v' + a" :transform="`rotate(${a} 200 200)`" stroke-width="1"
        d="M200 40 C214 66 214 92 200 112 M200 40 C186 66 186 92 200 112" />
    </g>
    <!-- curls between petals -->
    <g stroke-width="1.3" opacity=".8">
      <path v-for="a in petals" :key="'c' + a" :transform="`rotate(${a + 11.25} 200 200)`"
        d="M200 46 c-8 10 -2 22 8 20 c8 -2 6 -12 -1 -12" />
    </g>
    <!-- scalloped ring + dots -->
    <circle cx="200" cy="200" r="70" stroke-width="1.4" />
    <g stroke-width="1.2">
      <path v-for="a in inner" :key="'s' + a" :transform="`rotate(${a} 200 200)`" d="M182 133 Q200 116 218 133" />
    </g>
    <g fill="currentColor" stroke="none">
      <circle v-for="a in dots" :key="'d' + a" :transform="`rotate(${a} 200 200)`" cx="200" cy="150" r="2.2" />
    </g>
    <!-- inner leaves -->
    <g stroke-width="1.4">
      <path v-for="a in inner" :key="'l' + a" :transform="`rotate(${a} 200 200)`" d="M200 132 C214 150 214 166 200 176 C186 166 186 150 200 132 Z" />
    </g>
    <!-- eight-point star centre -->
    <g stroke-width="1.6">
      <rect x="172" y="172" width="56" height="56" rx="2" />
      <rect x="172" y="172" width="56" height="56" rx="2" transform="rotate(45 200 200)" />
      <circle cx="200" cy="200" r="14" />
    </g>
  </svg>
</template>
