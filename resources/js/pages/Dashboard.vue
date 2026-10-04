<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { BookOpen, Clock3, Compass, GraduationCap, Heart, MoonStar, Sparkles, ArrowRight } from 'lucide-vue-next';
import QurbaShell from '@/layouts/QurbaShell.vue';

defineProps<{ name?: string }>();

const tools = [
    { title: 'Read Quran', copy: 'Continue reading with translations, bookmarks and audio.', href: '/quran', icon: BookOpen },
    { title: 'Adhkar', copy: 'Morning, evening and daily remembrance.', href: '/zikr/adhkar', icon: Sparkles },
    { title: 'Tasbeeh', copy: 'A calm counter with goals, sound and haptics.', href: '/zikr/tasbeeh', icon: Heart },
    { title: 'Prayer Times', copy: 'Prayer schedule based on your selected location.', href: '/prayer', icon: Clock3 },
    { title: 'Qibla', copy: 'Find the Qibla with location and compass guidance.', href: '/qibla', icon: Compass },
    { title: 'Qurba Learning', copy: 'Explore Quran learning and future live classes.', href: '/learn', icon: GraduationCap },
];
</script>

<template>
    <Head title="My Qurba" />
    <QurbaShell>
        <section class="relative overflow-hidden rounded-[2rem] bg-[#073f31] px-6 py-8 text-white sm:px-8 lg:px-10 lg:py-10">
            <div class="pointer-events-none absolute -right-16 -top-20 size-72 rounded-full border border-[#d6b25e]/20"></div>
            <div class="relative max-w-3xl">
                <div class="mb-5 inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/[.06] px-3 py-1.5 text-xs font-semibold text-[#e3c77d]">
                    <MoonStar class="size-4" /> Your daily Qurba
                </div>
                <h1 class="font-serif text-4xl leading-tight sm:text-5xl">Assalamu Alaikum<span v-if="name">, {{ name }}</span></h1>
                <p class="mt-4 max-w-2xl text-sm leading-7 text-white/65 sm:text-base">Read, listen, remember and learn. Your worship tools and saved progress stay together in one peaceful space.</p>
                <div class="mt-7 flex flex-wrap gap-3">
                    <Link href="/quran" class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-[#d6b25e] px-5 py-2.5 text-sm font-bold text-[#073f31]">Continue Quran <ArrowRight class="size-4" /></Link>
                    <Link href="/prayer" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-white/15 bg-white/[.06] px-5 py-2.5 text-sm font-semibold text-white">Prayer times</Link>
                </div>
            </div>
        </section>

        <section class="mt-8">
            <p class="qurba-eyebrow">Essentials</p>
            <div class="mt-2 flex items-end justify-between gap-4">
                <h2 class="qurba-title text-3xl">Everything you need, without clutter.</h2>
            </div>
            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <Link v-for="item in tools" :key="item.href" :href="item.href" class="qurba-surface group rounded-[1.5rem] p-5 transition hover:-translate-y-0.5 hover:border-[#073f31]/20">
                    <span class="grid size-11 place-items-center rounded-2xl bg-emerald-50 text-[#073f31]"><component :is="item.icon" class="size-5" :stroke-width="1.7" /></span>
                    <h3 class="mt-6 font-serif text-2xl text-[#123b30]">{{ item.title }}</h3>
                    <p class="mt-2 min-h-12 text-sm leading-6 text-[#6b7873]">{{ item.copy }}</p>
                    <span class="mt-5 inline-flex items-center gap-2 text-sm font-bold text-[#073f31]">Open <ArrowRight class="size-4 transition group-hover:translate-x-1" /></span>
                </Link>
            </div>
        </section>
    </QurbaShell>
</template>
