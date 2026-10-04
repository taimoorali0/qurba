<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookOpen, Compass, MoonStar, Sparkles } from 'lucide-vue-next';

defineProps<{ title?: string; description?: string }>();
</script>

<template>
    <main class="min-h-screen bg-[#f7f3e9] text-[#17342c]">
        <div class="grid min-h-screen lg:grid-cols-[1.05fr_.95fr]">
            <section class="relative hidden overflow-hidden bg-[#073f31] p-10 text-white lg:flex lg:flex-col lg:justify-between xl:p-14">
                <div class="absolute inset-0 opacity-20" style="background-image:radial-gradient(circle at 18% 18%,#d6b25e 0 1px,transparent 1.5px);background-size:24px 24px"></div>
                <div class="relative">
                    <Link href="/" class="inline-flex items-center gap-3">
                        <span class="grid size-12 place-items-center rounded-2xl border border-[#d6b25e]/50 bg-white/10 text-[#e3c77d]"><MoonStar class="size-6" /></span>
                        <span><strong class="block font-serif text-3xl">Qurba</strong><small class="text-[10px] uppercase tracking-[.22em] text-white/55">Closer through remembrance</small></span>
                    </Link>
                </div>
                <div class="relative max-w-xl">
                    <p class="mb-4 text-xs font-bold uppercase tracking-[.2em] text-[#e3c77d]">Read · Listen · Remember · Learn</p>
                    <h2 class="font-serif text-5xl leading-[1.05] xl:text-6xl">Keep your heart close to Allah.</h2>
                    <p class="mt-5 max-w-lg text-base leading-7 text-white/65">Quran, Adhkar, Tasbeeh, prayer guidance and learning in one calm space designed for everyday remembrance.</p>
                    <div class="mt-9 grid grid-cols-3 gap-3">
                        <div class="rounded-2xl border border-white/10 bg-white/[.06] p-4"><BookOpen class="mb-3 size-5 text-[#e3c77d]" /><span class="text-sm font-semibold">Quran</span></div>
                        <div class="rounded-2xl border border-white/10 bg-white/[.06] p-4"><Sparkles class="mb-3 size-5 text-[#e3c77d]" /><span class="text-sm font-semibold">Adhkar</span></div>
                        <div class="rounded-2xl border border-white/10 bg-white/[.06] p-4"><Compass class="mb-3 size-5 text-[#e3c77d]" /><span class="text-sm font-semibold">Qibla</span></div>
                    </div>
                </div>
                <p class="relative text-xs text-white/40">Qurba · A peaceful companion for your daily worship.</p>
            </section>

            <section class="flex items-center justify-center px-5 py-10 sm:px-8 lg:px-12">
                <div class="w-full max-w-[460px]">
                    <Link href="/" class="mb-10 inline-flex items-center gap-3 lg:hidden">
                        <span class="grid size-11 place-items-center rounded-2xl bg-[#073f31] text-[#e3c77d]"><MoonStar class="size-5" /></span>
                        <span><strong class="block font-serif text-2xl">Qurba</strong><small class="text-[9px] uppercase tracking-[.18em] text-[#6d7b75]">Closer through remembrance</small></span>
                    </Link>
                    <div class="mb-8">
                        <p class="mb-2 text-xs font-bold uppercase tracking-[.16em] text-[#a67c2e]">Welcome to Qurba</p>
                        <h1 class="font-serif text-4xl leading-tight text-[#0b3b2d]">{{ title }}</h1>
                        <p class="mt-3 text-sm leading-6 text-[#66746e]">{{ description }}</p>
                    </div>
                    <slot />
                    <p class="mt-10 text-center text-xs text-[#89938f]">Your worship tools stay simple, calm and accessible.</p>
                </div>
            </section>
        </div>
    </main>
</template>
