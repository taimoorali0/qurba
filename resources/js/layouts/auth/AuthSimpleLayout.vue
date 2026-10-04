<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { MoonStar } from 'lucide-vue-next';
defineProps<{ title?: string; description?: string }>();
</script>

<template>
  <main class="min-h-dvh bg-[#fbf8f0] text-[#17342c]">
    <header class="border-b border-[#073f31]/[.07] bg-[#fffdf8]/90 backdrop-blur">
      <div class="mx-auto flex h-[72px] max-w-7xl items-center justify-between px-5 md:px-8">
        <Link href="/" class="flex items-center gap-2.5" aria-label="Qurba home">
          <img src="/brand/qurba-icon-96.png" alt="" class="size-9" />
          <img src="/brand/qurba-logo.png" alt="Qurba" class="h-9 w-auto" />
        </Link>
        <Link href="/" class="text-sm font-semibold text-[#416058] hover:text-[#073f31]">Back to Qurba</Link>
      </div>
    </header>

    <section class="mx-auto grid min-h-[calc(100dvh-72px)] max-w-7xl items-center gap-10 px-5 py-10 md:px-8 lg:grid-cols-[.9fr_1.1fr] lg:py-14">
      <div class="hidden max-w-lg lg:block">
        <span class="inline-flex items-center gap-2 rounded-full bg-[#073f31]/[.06] px-3 py-1.5 text-xs font-bold uppercase tracking-[.14em] text-[#8f6a24]">
          <MoonStar class="size-4" /> Closer through remembrance
        </span>
        <h2 class="mt-5 font-serif text-5xl leading-[1.05] text-[#073f31]">Your Qurba, on every device.</h2>
        <p class="mt-5 max-w-md text-base leading-7 text-[#64736d]">Sign in only when you want backup and sync. Quran reading, Adhkar and Tasbeeh remain simple and accessible.</p>
        <div class="mt-8 flex gap-6 border-t border-[#073f31]/10 pt-6 text-sm text-[#52665f]">
          <span>Quran progress</span><span>Bookmarks</span><span>Remembrance</span>
        </div>
      </div>

      <div class="mx-auto w-full max-w-[500px] rounded-[1.75rem] border border-[#073f31]/10 bg-[#fffdf8] p-6 shadow-[0_24px_80px_rgba(7,63,49,.08)] sm:p-8">
        <div class="mb-7">
          <p class="mb-2 text-[11px] font-bold uppercase tracking-[.16em] text-[#a67c2e]">Qurba account</p>
          <h1 class="font-serif text-3xl text-[#073f31] sm:text-4xl">{{ title }}</h1>
          <p class="mt-2 text-sm leading-6 text-[#697872]">{{ description }}</p>
        </div>
        <slot />
      </div>
    </section>
  </main>
</template>
