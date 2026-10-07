<!-- ===== QURBA SHELL — START ===== -->
<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Home, BookOpen, CircleDot, Clock, Compass, GraduationCap, User, ChevronDown, Search, Sparkles } from 'lucide-vue-next';
import { useLocale } from '../composables/useLocale';
import type { QurbaLocale } from '../lib/i18n';
import AppStatus from '../components/AppStatus.vue';
import SoundControl from '../components/SoundControl.vue';
import { initSounds } from '../lib/sounds';

const { t } = useI18n();
const { locale, setLocale } = useLocale();
const page = usePage();
const path = computed(() => page.url.split('?')[0]);
const isActive = (href: string) => (href === '/' ? path.value === '/' : path.value.startsWith(href));

// Mobile bottom bar: 5 items. Desktop top bar shows the rest too.
const primary = [
  { key: 'home', href: '/', icon: Home },
  { key: 'quran', href: '/quran', icon: BookOpen },
  { key: 'zikr', href: '/zikr', icon: CircleDot },
  { key: 'prayer', href: '/prayer', icon: Clock },
  { key: 'profile', href: '/profile', icon: User },
];
// Desktop top bar: main sections, the rest under "More"
const desktop = [primary[0], primary[1], primary[2], { key: 'learn', href: '/learn', icon: GraduationCap }];
const more = [
  { key: 'prayer', href: '/prayer', icon: Clock },
  { key: 'qibla', href: '/qibla', icon: Compass },
  { key: 'names', href: '/names', icon: Sparkles },
  { key: 'profile', href: '/profile', icon: User },
];
const moreActive = computed(() => more.some((m) => isActive(m.href)));
const user = computed(() => (page.props as any).auth?.user ?? null);
const initial = computed(() => (user.value?.name ?? '?').trim().charAt(0).toUpperCase());
initSounds();
function hoverMore(event: MouseEvent, open: boolean) {
  if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) (event.currentTarget as HTMLDetailsElement).open = open;
}
function closeMore(event: KeyboardEvent) {
  const menu = event.currentTarget as HTMLDetailsElement;
  menu.open = false; menu.querySelector('summary')?.focus();
}
const q = ref('');
function search() { const term = q.value.trim(); if (term) router.visit(`/quran?q=${encodeURIComponent(term)}`); }
const langs: { code: QurbaLocale; label: string }[] = [
  { code: 'en', label: 'English' }, { code: 'ar', label: 'العربية' }, { code: 'ur', label: 'اردو' },
];
</script>

<template>
  <div class="bg-islamic min-h-dvh text-ink">
    <header class="safe-top sticky top-0 z-30 border-b border-line bg-paper/85 backdrop-blur">
      <div class="mx-auto flex h-16 max-w-6xl md:h-20 items-center gap-2 sm:gap-4 px-4 md:px-8">
        <Link href="/" class="flex shrink-0 items-center gap-2" aria-label="Qurba home">
          <!-- Mobile: icon + name -->
          <img src="/brand/qurba-icon-96.png" alt="" class="size-9 md:hidden" width="36" height="36" />
          <span class="font-display text-2xl text-emerald-900 md:hidden">Qurba</span>
          <!-- Tablet/desktop: full logo -->
          <img src="/brand/qurba-logo.png" alt="Qurba — Closer Through Remembrance" class="hidden h-12 w-auto md:block" height="48" />
        </Link>

        <nav class="hidden flex-1 items-center gap-1 md:flex">
          <Link v-for="item in desktop" :key="item.key" :href="item.href"
            class="relative px-3 py-2 text-sm transition-colors"
            :class="isActive(item.href) ? 'font-medium text-emerald-900' : 'text-ink-soft hover:text-emerald-900'">
            {{ t('nav.' + item.key) }}
            <span v-if="isActive(item.href)" class="absolute inset-x-3 -bottom-0.5 h-0.5 rounded-full bg-emerald-900" />
          </Link>
          <details class="group relative" @mouseenter="hoverMore($event, true)" @mouseleave="hoverMore($event, false)" @keydown.esc="closeMore($event)">
            <summary class="flex cursor-pointer list-none items-center gap-1 px-3 py-2 text-sm"
              :class="moreActive ? 'font-medium text-emerald-900' : 'text-ink-soft hover:text-emerald-900'">
              {{ t('nav.more') }} <ChevronDown class="size-3.5 transition-transform group-open:rotate-180" />
            </summary>
            <div class="absolute start-0 top-full z-40 w-48 rounded-2xl border border-line bg-paper p-1.5 shadow-lg">
              <Link v-for="m in more" :key="m.key" :href="m.href"
                class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm hover:bg-cream"
                :class="isActive(m.href) ? 'text-emerald-900' : 'text-ink'">
                <component :is="m.icon" class="size-4 text-emerald-700" /> {{ t('nav.' + m.key) }}
              </Link>

            </div>
          </details>
        </nav>

        <div class="ms-auto flex items-center gap-2">
          <form role="search" class="relative hidden lg:block" @submit.prevent="search">
            <Search class="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-ink-soft" />
            <input v-model="q" type="search" :placeholder="t('nav.search')" :aria-label="t('nav.search')"
              class="w-56 rounded-full border-line bg-paper py-1.5 ps-9 pe-3 text-sm placeholder:text-ink-soft focus:border-gold-500 focus:ring-0" />
          </form>
          <Link href="/explore" aria-label="Explore" class="grid size-10 place-items-center rounded-full border border-line text-emerald-900 md:hidden"><Compass class="size-5" /></Link>
          <SoundControl class="hidden sm:block" />
          <label class="sr-only" for="lang">{{ t('lang') }}</label>
          <select id="lang" :value="locale" @change="setLocale(($event.target as HTMLSelectElement).value as QurbaLocale)"
            class="rounded-full border border-line bg-paper py-1.5 ps-4 pe-9 text-sm">
            <option v-for="l in langs" :key="l.code" :value="l.code">{{ l.label }}</option>
          </select>
          <Link v-if="user" href="/profile" class="hidden md:grid size-9 place-items-center rounded-full bg-emerald-900 text-sm font-medium text-cream"
            :aria-label="t('nav.profile')">{{ initial }}</Link>
          <Link v-else href="/login" class="hidden whitespace-nowrap rounded-full bg-emerald-900 px-5 py-2 text-sm text-cream hover:bg-emerald-700 md:inline-flex">
            {{ t('nav.signIn') }}
          </Link>
        </div>
      </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 pb-28 pt-6 md:px-8 md:pb-12">
      <AppStatus class="mb-5" />
      <slot />
    </main>

    <!-- Mobile bottom navigation -->
    <nav class="safe-bottom fixed inset-x-0 bottom-0 z-30 border-t border-line bg-paper/95 shadow-[0_-6px_20px_rgba(16,24,20,0.05)] backdrop-blur md:hidden">
      <ul class="grid grid-cols-5">
        <li v-for="item in primary" :key="item.key">
          <Link :href="item.href" class="flex flex-col items-center gap-1 py-2.5 text-[11px]"
            :class="isActive(item.href) ? 'text-emerald-900' : 'text-ink-soft'">
            <component :is="item.icon" class="size-5" :stroke-width="isActive(item.href) ? 2.4 : 1.8" />
            {{ t('nav.' + item.key) }}
            <span class="h-0.5 w-5 rounded-full" :class="isActive(item.href) ? 'bg-gold-500' : 'bg-transparent'" />
          </Link>
        </li>
      </ul>
    </nav>
  </div>
</template>
<!-- ===== QURBA SHELL — END ===== -->
