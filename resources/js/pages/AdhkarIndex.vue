<!-- ===== QURBA: Adhkar categories ===== -->
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { BookHeart, ChevronLeft, Heart, HandHeart, Moon, Plane, Sparkles, Sun, Sunset } from 'lucide-vue-next';
import QurbaShell from '../layouts/QurbaShell.vue';
import { ak } from '../lib/adhkarLocal';

defineProps<{ categories: { slug: string; name: Record<string, string>; count: number }[] }>();
const { t, locale } = useI18n();
const icons: Record<string, any> = { morning: Sun, evening: Sunset, 'after-salah': Sparkles, 'sleep-wake': Moon, travel: Plane, 'daily-duas': HandHeart, 'quranic-duas': BookHeart };
const name = (n: Record<string, string>) => n[locale.value] ?? n.en;
</script>

<template>
  <Head :title="t('home.adhkar')" />
  <QurbaShell>
    <div class="flex items-center gap-3">
      <Link href="/zikr" class="grid size-9 place-items-center rounded-full border border-line bg-paper" :aria-label="t('nav.zikr')"><ChevronLeft class="size-4 rtl:rotate-180" /></Link>
      <h1 class="font-display text-3xl text-emerald-900">{{ t('home.adhkar') }}</h1>
    </div>
    <ul class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <li v-for="c in categories" :key="c.slug">
        <Link :href="`/zikr/adhkar/${c.slug}`" class="flex items-center gap-4 rounded-[var(--radius-tile)] border border-line bg-paper px-5 py-4 hover:border-gold-500">
          <component :is="icons[c.slug] ?? Sparkles" class="size-6 text-emerald-700" :stroke-width="1.6" />
          <span class="flex-1"><span class="block font-medium text-ink">{{ name(c.name) }}</span>
            <span class="text-xs text-ink-soft">{{ c.count ? t('adhkar.items', { n: c.count }) : t('adhkar.inReview') }}</span></span>
        </Link>
      </li>
      <li>
        <Link :href="`/zikr/adhkar-favorites?ids=${ak.favorites.join(',')}`" class="flex items-center gap-4 rounded-[var(--radius-tile)] border border-line bg-paper px-5 py-4 hover:border-gold-500">
          <Heart class="size-6 text-gold-600" :stroke-width="1.6" />
          <span class="flex-1"><span class="block font-medium text-ink">{{ t('adhkar.favorites') }}</span>
            <span class="text-xs text-ink-soft">{{ t('adhkar.items', { n: ak.favorites.length }) }}</span></span>
        </Link>
      </li>
    </ul>
  </QurbaShell>
</template>
