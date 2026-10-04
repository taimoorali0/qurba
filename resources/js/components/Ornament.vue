<!-- ===== QURBA: gold divider with an eight-point star ===== -->
<script setup lang="ts">
defineProps<{ light?: boolean; align?: 'start' | 'center' }>();
</script>

<template>
  <div aria-hidden="true" class="flex items-center gap-2" :class="align === 'start' ? 'justify-start' : 'justify-center'">
    <span class="h-px w-10 sm:w-16" :class="light ? 'bg-gradient-to-r from-transparent to-gold-200/80' : 'bg-gradient-to-r from-transparent to-gold-500/70'" />
    <svg viewBox="0 0 24 24" class="size-4" :class="light ? 'text-gold-200' : 'text-gold-500'" fill="currentColor">
      <path d="M12 2l2.6 6.4L21 11l-6.4 2.6L12 20l-2.6-6.4L3 11l6.4-2.6z" opacity=".35" transform="rotate(22.5 12 11)" />
      <path d="M12 2l2.6 6.4L21 11l-6.4 2.6L12 20l-2.6-6.4L3 11l6.4-2.6z" />
    </svg>
    <span class="h-px w-10 sm:w-16" :class="light ? 'bg-gradient-to-l from-transparent to-gold-200/80' : 'bg-gradient-to-l from-transparent to-gold-500/70'" />
  </div>
</template>
