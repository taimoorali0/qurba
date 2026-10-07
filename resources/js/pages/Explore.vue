<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { BookOpen, Heart, Headphones, GraduationCap, Calendar, ArrowRight } from 'lucide-vue-next';
import QurbaShell from '../layouts/QurbaShell.vue';
const sections = [
  { title: 'Quran', description: 'Read the Quran, choose a translation and continue your recitation.', href: '/quran', icon: BookOpen },
  { title: 'Duas & Adhkar', description: 'Morning, evening and everyday remembrance with references.', href: '/zikr/adhkar', icon: Heart },
  { title: 'Hadith', description: 'Browse published narrations, their references and translations.', href: '/library/hadith', icon: BookOpen },
  { title: 'Seerat-un-Nabi ﷺ', description: 'Read published chapters about the life of the Prophet ﷺ.', href: '/library/seerah', icon: BookOpen },
  { title: 'Kids', description: 'Explore published lessons for children and families.', href: '/library/kids', icon: GraduationCap },
  { title: 'Hamd & Naats', description: 'Browse published recordings and listen in the app.', href: '/library/naats', icon: Headphones },
  { title: 'Ruqyah', description: 'Browse published recitations with their sources.', href: '/library/ruqyah', icon: Heart },
  { title: '99 Names of Allah', description: 'Read the names and meanings; listen when recordings are available.', href: '/names', icon: Heart },
  { title: 'Prayer & Qibla', description: 'Set your location, check prayer times and find the Qibla.', href: '/prayer', icon: Calendar },
];
</script>
<template>
  <Head title="Explore Qurba" />
  <QurbaShell>
    <section class="mb-8 rounded-3xl bg-emerald-900 p-7 text-white md:p-10">
      <p class="mb-3 text-sm text-emerald-100">QURBA · EXPLORE</p>
      <h1 class="text-3xl font-semibold md:text-4xl">A little closer, every day.</h1>
      <p class="mt-4 max-w-xl leading-relaxed text-emerald-100">Read, remember and learn in one calm space. Choose where you want to begin.</p>
      <Link href="/quran" class="mt-6 inline-flex items-center gap-2 rounded-full bg-white px-5 py-3 text-emerald-900">Open Quran <ArrowRight class="size-4" /></Link>
    </section>
    <h2 class="mb-5 text-2xl font-semibold text-emerald-900">Your library</h2>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <Link v-for="section in sections" :key="section.title" :href="section.href" class="rounded-2xl border border-line bg-paper p-6 transition-colors hover:border-emerald-700">
        <component :is="section.icon" class="mb-5 size-6 text-emerald-700" />
        <h3 class="text-xl font-semibold text-emerald-900">{{ section.title }}</h3>
        <p class="mt-3 leading-relaxed text-ink-soft">{{ section.description }}</p>
        <span class="mt-5 inline-flex items-center gap-2 text-sm text-emerald-900">Explore <ArrowRight class="size-4" /></span>
      </Link>
    </div>
  </QurbaShell>
</template>
