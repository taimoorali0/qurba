<!-- ===== QURBA: page title with Arabic calligraphy subtitle and ornament ===== -->
<script setup lang="ts">
defineProps<{ title: string; arabic?: string; sub?: string }>();
</script>

<template>
  <div class="flex flex-wrap items-end justify-between gap-3">
    <div class="min-w-0">
      <p v-if="arabic" dir="rtl" lang="ar" class="font-quran text-xl leading-relaxed text-gold-600 md:text-2xl">{{ arabic }}</p>
      <h1 class="mt-1 font-display text-3xl text-emerald-900 md:text-4xl">{{ title }}</h1>
      <p v-if="sub" class="mt-1 text-sm text-ink-soft">{{ sub }}</p>
    </div>
    <slot />
  </div>
</template>
