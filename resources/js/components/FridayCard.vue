<!-- ===== QURBA: Friday reminder to read Surah Al-Kahf (shown on Fridays only) ===== -->
<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookOpen } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>

<template>
  <section class="flex flex-wrap items-center gap-4 rounded-[var(--radius-tile)] border border-gold-500/40 bg-gradient-to-r from-gold-200/60 to-paper p-5 shadow-soft">
    <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-emerald-900 text-gold-500"><BookOpen class="size-5" /></span>
    <div class="min-w-0 flex-1">
      <p class="font-display text-lg text-emerald-900">{{ t('friday.title') }}</p>
      <p class="text-sm text-ink-soft">{{ t('friday.body') }}</p>
    </div>
    <Link href="/quran/18" class="rounded-full bg-emerald-900 px-5 py-2 text-sm text-cream hover:bg-emerald-700">{{ t('friday.cta') }}</Link>
  </section>
</template>
