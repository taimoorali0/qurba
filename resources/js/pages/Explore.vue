<!-- ===== QURBA: library hub (Quran, adhkar and tools live on the home screen) ===== -->
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, BookMarked, BookOpen, GraduationCap, Headphones, ScrollText, ShieldCheck } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
import PageTitle from '../components/PageTitle.vue';
import QurbaShell from '../layouts/QurbaShell.vue';

const { t } = useI18n();
const sections = [
    { title: 'explore.books', desc: 'explore.d_books', href: '/library/books', icon: BookMarked },
    { title: 'nav.hadith', desc: 'explore.d_hadith', href: '/library/hadith', icon: ScrollText },
    { title: 'nav.seerah', desc: 'explore.d_seerah', href: '/library/seerah', icon: BookOpen },
    { title: 'nav.kids', desc: 'explore.d_kids', href: '/library/kids', icon: GraduationCap },
    { title: 'nav.naats', desc: 'explore.d_naats', href: '/library/naats', icon: Headphones },
    { title: 'nav.ruqyah', desc: 'explore.d_ruqyah', href: '/library/ruqyah', icon: ShieldCheck },
];
</script>

<template>
    <Head :title="t('explore.title')" />
    <QurbaShell>
        <PageTitle :title="t('explore.title')" arabic="المكتبة" :sub="t('explore.sub')" />
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="s in sections"
                :key="s.href"
                :href="s.href"
                class="group flex flex-col rounded-[var(--radius-tile)] bg-paper p-5 shadow-soft transition-shadow hover:shadow-lift"
            >
                <span class="grid size-11 place-items-center rounded-2xl bg-teal-100"
                    ><component :is="s.icon" class="size-5 text-emerald-700" :stroke-width="1.7"
                /></span>
                <h2 class="mt-4 font-display text-xl text-emerald-900">{{ t(s.title) }}</h2>
                <p class="mt-2 flex-1 text-sm leading-relaxed text-ink-soft">{{ t(s.desc) }}</p>
                <span class="mt-4 inline-flex items-center gap-1.5 text-sm text-emerald-900"
                    >{{ t('explore.open') }} <ArrowRight class="size-4 transition-transform group-hover:translate-x-0.5 rtl:rotate-180"
                /></span>
            </Link>
        </div>
    </QurbaShell>
</template>
