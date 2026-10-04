<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft } from 'lucide-vue-next';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="relative flex min-h-svh flex-col items-center justify-center overflow-hidden bg-cream p-6 text-ink md:p-10">
        <!-- Soft brand glow behind the card -->
        <div aria-hidden="true" class="pointer-events-none absolute -top-40 start-1/2 size-[36rem] -translate-x-1/2 rounded-full bg-gold-200/50 blur-3xl rtl:translate-x-1/2" />

        <Link href="/" class="absolute start-4 top-4 inline-flex items-center gap-1 rounded-full border border-line bg-paper px-3 py-1.5 text-sm text-ink-soft hover:text-emerald-900 md:start-8 md:top-8">
            <ChevronLeft class="size-4 rtl:rotate-180" /> Qurba
        </Link>

        <div class="relative w-full max-w-md rounded-[var(--radius-sheet)] border border-line bg-paper px-6 py-8 shadow-sm md:px-10 md:py-10">
            <div class="flex flex-col items-center gap-4">
                <Link href="/" aria-label="Qurba home">
                    <img src="/brand/qurba-logo.png" alt="Qurba — Closer Through Remembrance" class="h-16 w-auto" height="64" />
                </Link>
                <div class="space-y-1 text-center">
                    <h1 class="font-display text-2xl text-emerald-900">{{ title }}</h1>
                    <p class="text-sm text-ink-soft">{{ description }}</p>
                </div>
            </div>
            <div class="mt-8">
                <slot />
            </div>
        </div>
    </div>
</template>
