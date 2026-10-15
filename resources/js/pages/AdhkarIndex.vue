<!-- ===== QURBA: Adhkar categories, duas by situation and search ===== -->
<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeft, Heart, Search, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import PageTitle from '../components/PageTitle.vue';
import QurbaShell from '../layouts/QurbaShell.vue';
import { categoryIcon } from '../lib/adhkarIcons';
import { ak } from '../lib/adhkarLocal';

interface Category { slug: string; name: Record<string, string>; count: number }
interface Result { id: number; ar: string; reference: string | null; tr: Record<string, string> }
const props = defineProps<{ categories: Category[]; q?: string; results?: Result[] | null }>();
const { t, locale } = useI18n();
const name = (n: Record<string, string>) => n[locale.value] ?? n.en;

// Categories for a moment in life; everything else is daily remembrance.
const SITUATIONS = ['travel', 'illness', 'distress', 'knowledge', 'forgiveness', 'protection', 'home-family', 'food-drink', 'weather', 'masjid'];
const groups = computed(() => [
  { key: 'daily', items: props.categories.filter((c) => !SITUATIONS.includes(c.slug)) },
  { key: 'situations', items: props.categories.filter((c) => SITUATIONS.includes(c.slug)) },
]);

const query = ref(props.q ?? '');
function search() { router.get('/zikr/adhkar', query.value.trim() ? { q: query.value.trim() } : {}, { preserveState: true, preserveScroll: true }); }
function clear() { query.value = ''; search(); }
const translation = (r: Result) => r.tr[locale.value === 'ar' ? 'en' : locale.value] ?? r.tr.en ?? '';
</script>

<template>
  <Head :title="t('home.adhkar')" />
  <QurbaShell>
    <div class="flex items-center gap-3">
      <Link href="/zikr" class="grid size-9 place-items-center rounded-full border border-line bg-paper" :aria-label="t('nav.zikr')"><ChevronLeft class="size-4 rtl:rotate-180" /></Link>
      <PageTitle :title="t('home.adhkar')" arabic="الأذكار" class="flex-1" />
    </div>

    <form role="search" class="relative mt-6" @submit.prevent="search">
      <Search class="pointer-events-none absolute start-4 top-1/2 size-4 -translate-y-1/2 text-ink-soft" />
      <input v-model="query" type="search" maxlength="60" :placeholder="t('adhkar.search')" :aria-label="t('adhkar.search')"
        class="w-full rounded-full border-line bg-paper py-3 ps-11 pe-11 placeholder:text-ink-soft focus:border-gold-500 focus:ring-0" />
      <button v-if="q" type="button" class="absolute end-3 top-1/2 grid size-8 -translate-y-1/2 place-items-center rounded-full text-ink-soft hover:bg-cream" :aria-label="t('adhkar.clear')" @click="clear">
        <X class="size-4" />
      </button>
    </form>

    <!-- Search results -->
    <section v-if="results" class="mt-6" aria-live="polite">
      <h2 class="font-display text-lg text-emerald-900">{{ t('adhkar.results', { n: results.length, q }) }}</h2>
      <p v-if="!results.length" class="mt-3 rounded-[var(--radius-tile)] bg-paper p-5 text-ink-soft shadow-soft">{{ t('adhkar.noResults') }}</p>
      <ul class="mt-3 space-y-3">
        <li v-for="r in results" :key="r.id" class="rounded-[var(--radius-tile)] bg-paper p-5 shadow-soft">
          <p dir="rtl" lang="ar" class="font-quran text-2xl leading-[2.2] text-ink">{{ r.ar }}</p>
          <p v-if="translation(r)" class="mt-2 text-sm leading-relaxed text-ink" :dir="locale === 'ur' ? 'rtl' : 'ltr'">{{ translation(r) }}</p>
          <p v-if="r.reference" class="mt-2 text-xs text-ink-soft">{{ r.reference }}</p>
        </li>
      </ul>
    </section>

    <template v-else>
      <section v-for="g in groups" :id="g.key" :key="g.key" class="mt-8 scroll-mt-24 first-of-type:mt-6">
        <h2 v-if="g.items.length" class="mb-3 font-display text-lg text-emerald-900">{{ t('adhkar.group_' + g.key) }}</h2>
        <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <li v-for="c in g.items" :key="c.slug">
            <Link :href="`/zikr/adhkar/${c.slug}`" class="flex items-center gap-4 rounded-[var(--radius-tile)] bg-paper px-5 py-4 shadow-soft transition-shadow hover:shadow-lift">
              <component :is="categoryIcon(c.slug)" class="size-6 text-emerald-700" :stroke-width="1.6" />
              <span class="flex-1"><span class="block font-medium text-ink">{{ name(c.name) }}</span>
                <span class="text-xs text-ink-soft">{{ c.count ? t('adhkar.items', { n: c.count }) : t('adhkar.inReview') }}</span></span>
            </Link>
          </li>
          <li v-if="g.key === 'daily'">
            <Link :href="`/zikr/adhkar-favorites?ids=${ak.favorites.join(',')}`" class="flex items-center gap-4 rounded-[var(--radius-tile)] bg-paper px-5 py-4 shadow-soft transition-shadow hover:shadow-lift">
              <Heart class="size-6 text-gold-600" :stroke-width="1.6" />
              <span class="flex-1"><span class="block font-medium text-ink">{{ t('adhkar.favorites') }}</span>
                <span class="text-xs text-ink-soft">{{ t('adhkar.items', { n: ak.favorites.length }) }}</span></span>
            </Link>
          </li>
        </ul>
      </section>
    </template>
  </QurbaShell>
</template>
