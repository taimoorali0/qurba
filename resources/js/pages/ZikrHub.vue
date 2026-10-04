<!-- ===== QURBA: Zikr hub ===== -->
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { CircleDot, Sparkles, Sun } from 'lucide-vue-next';
import QurbaShell from '../layouts/QurbaShell.vue';
import { todayTotal } from '../lib/tasbeeh';
import { memorised } from '../lib/asmaulHusna';
const { t } = useI18n();
</script>

<template>
  <Head :title="t('nav.zikr')" />
  <QurbaShell>
    <h1 class="font-display text-3xl text-emerald-900 md:text-4xl">{{ t('nav.zikr') }}</h1>
    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <Link href="/zikr/adhkar" class="rounded-[var(--radius-sheet)] border border-line bg-paper p-6 hover:border-gold-500">
        <Sun class="size-7 text-emerald-700" />
        <h2 class="mt-4 font-display text-2xl text-emerald-900">{{ t('home.adhkar') }}</h2>
        <p class="mt-1 text-sm text-ink-soft">{{ t('adhkar.hubText') }}</p>
      </Link>
      <Link href="/zikr/tasbeeh" class="rounded-[var(--radius-sheet)] bg-emerald-900 p-6 text-cream">
        <CircleDot class="size-7 text-gold-500" />
        <h2 class="mt-4 font-display text-2xl">{{ t('home.tasbeeh') }}</h2>
        <p class="mt-1 text-sm text-gold-200">{{ t('tasbeeh.todayCount', { n: todayTotal() }) }}</p>
      </Link>
      <Link href="/names" class="rounded-[var(--radius-sheet)] border border-line bg-paper p-6 hover:border-gold-500">
        <Sparkles class="size-7 text-gold-600" />
        <h2 class="mt-4 font-display text-2xl text-emerald-900">{{ t('names.title') }}</h2>
        <p class="mt-1 text-sm text-ink-soft">{{ t('names.progress', { n: memorised.ids.length }) }}</p>
      </Link>
    </div>
  </QurbaShell>
</template>
