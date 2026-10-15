<!-- ===== QURBA: every section in one place (mobile "More" tab) ===== -->
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Baby, BookOpen, Brain, CalendarDays, CircleDot, Clock, Coins, Compass, GraduationCap, HandHeart, Library, ListMusic, Sparkles, Sun, Target, User } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
import PageTitle from '../components/PageTitle.vue';
import QurbaShell from '../layouts/QurbaShell.vue';

const { t } = useI18n();
const groups = [
  { key: 'quran', items: [
    { key: 'nav.quran', href: '/quran', icon: BookOpen }, { key: 'nav.khatam', href: '/khatam', icon: Target },
    { key: 'nav.hifz', href: '/hifz', icon: Brain }, { key: 'nav.playlists', href: '/playlists', icon: ListMusic },
  ] },
  { key: 'worship', items: [
    { key: 'home.adhkar', href: '/zikr/adhkar', icon: Sun }, { key: 'nav.duas', href: '/zikr/adhkar#situations', icon: HandHeart },
    { key: 'home.tasbeeh', href: '/zikr/tasbeeh', icon: CircleDot }, { key: 'nav.prayer', href: '/prayer', icon: Clock },
    { key: 'nav.qibla', href: '/qibla', icon: Compass }, { key: 'nav.names', href: '/names', icon: Sparkles },
  ] },
  { key: 'tools', items: [
    { key: 'nav.calendar', href: '/calendar', icon: CalendarDays }, { key: 'nav.zakat', href: '/zakat', icon: Coins },
  ] },
  { key: 'learn', items: [
    { key: 'nav.explore', href: '/explore', icon: Library }, { key: 'nav.learn', href: '/learn', icon: GraduationCap },
    { key: 'nav.kids', href: '/kids', icon: Baby }, { key: 'nav.profile', href: '/profile', icon: User },
  ] },
];
</script>

<template>
  <Head :title="t('nav.more')" />
  <QurbaShell>
    <PageTitle :title="t('nav.more')" />
    <section v-for="g in groups" :key="g.key" class="mt-6">
      <h2 class="mb-3 text-sm font-medium uppercase tracking-wide text-ink-soft">{{ t('more.' + g.key) }}</h2>
      <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
        <li v-for="i in g.items" :key="i.href">
          <Link :href="i.href" class="flex items-center gap-3 rounded-[var(--radius-tile)] bg-paper px-4 py-3.5 shadow-soft hover:shadow-lift">
            <component :is="i.icon" class="size-5 shrink-0 text-emerald-700" :stroke-width="1.7" />
            <span class="text-sm font-medium text-ink">{{ t(i.key) }}</span>
          </Link>
        </li>
      </ul>
    </section>
  </QurbaShell>
</template>
