<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Home, BookOpen, CircleDot, Clock3, Compass, GraduationCap, User, ChevronDown } from 'lucide-vue-next';
import { useLocale } from '../composables/useLocale';
import type { QurbaLocale } from '../lib/i18n';
import AppStatus from '../components/AppStatus.vue';

const { t } = useI18n();
const { locale, setLocale } = useLocale();
const page = usePage();
const path = computed(() => page.url.split('?')[0]);
const isActive = (href: string) => href === '/' ? path.value === '/' : path.value.startsWith(href);

const primary = [
  { key: 'home', href: '/', icon: Home },
  { key: 'quran', href: '/quran', icon: BookOpen },
  { key: 'zikr', href: '/zikr', icon: CircleDot },
  { key: 'learn', href: '/learn', icon: GraduationCap },
  { key: 'profile', href: '/profile', icon: User },
];
const desktop = [
  ...primary.slice(0, 3),
  { key: 'prayer', href: '/prayer', icon: Clock3 },
  { key: 'qibla', href: '/qibla', icon: Compass },
  primary[3],
];
const langs: { code: QurbaLocale; label: string }[] = [
  { code: 'en', label: 'EN' }, { code: 'ar', label: 'العربية' }, { code: 'ur', label: 'اردو' },
];
</script>

<template>
  <div class="qurba-app min-h-dvh">
    <header class="safe-top sticky top-0 z-30 border-b border-emerald-950/5 bg-[#fbf8f0]/92 backdrop-blur-xl">
      <div class="mx-auto flex h-[68px] max-w-7xl items-center gap-5 px-4 md:h-[76px] md:px-8">
        <Link href="/" class="flex shrink-0 items-center gap-2.5" aria-label="Qurba home">
          <img src="/brand/qurba-icon-96.png" alt="" class="size-9" width="36" height="36" />
          <div class="hidden sm:block">
            <img src="/brand/qurba-logo.png" alt="Qurba" class="h-9 w-auto" height="36" />
          </div>
        </Link>

        <nav class="hidden flex-1 items-center justify-center gap-1 md:flex">
          <Link v-for="item in desktop" :key="item.key" :href="item.href"
            class="rounded-xl px-3.5 py-2 text-sm font-medium transition"
            :class="isActive(item.href) ? 'bg-emerald-950 text-white shadow-sm' : 'text-ink-soft hover:bg-white/70 hover:text-emerald-950'">
            {{ t('nav.' + item.key) }}
          </Link>
        </nav>

        <div class="ms-auto flex items-center gap-2">
          <label class="relative hidden sm:block">
            <span class="sr-only">{{ t('lang') }}</span>
            <select :value="locale" @change="setLocale(($event.target as HTMLSelectElement).value as QurbaLocale)"
              class="appearance-none rounded-xl border border-emerald-950/10 bg-white/70 py-2 ps-3 pe-8 text-xs font-semibold text-emerald-950 shadow-sm">
              <option v-for="l in langs" :key="l.code" :value="l.code">{{ l.label }}</option>
            </select>
            <ChevronDown class="pointer-events-none absolute end-2.5 top-1/2 size-3.5 -translate-y-1/2 text-emerald-900/60" />
          </label>
          <Link href="/profile" class="hidden size-10 place-items-center rounded-xl border border-emerald-950/10 bg-white/70 text-emerald-950 shadow-sm md:grid"
            :aria-label="t('nav.profile')"><User class="size-[18px]" /></Link>
        </div>
      </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 pb-28 pt-5 md:px-8 md:pb-12 md:pt-8">
      <AppStatus class="mb-5" />
      <slot />
    </main>

    <nav class="safe-bottom fixed inset-x-0 bottom-0 z-30 border-t border-emerald-950/10 bg-[#fffdf8]/95 shadow-[0_-10px_35px_rgba(11,59,45,.07)] backdrop-blur-xl md:hidden">
      <ul class="grid grid-cols-5 px-1">
        <li v-for="item in primary" :key="item.key">
          <Link :href="item.href" class="relative flex min-h-[62px] flex-col items-center justify-center gap-1 text-[10px] font-medium"
            :class="isActive(item.href) ? 'text-emerald-950' : 'text-ink-soft'">
            <span class="grid size-8 place-items-center rounded-xl transition" :class="isActive(item.href) ? 'bg-emerald-100' : ''">
              <component :is="item.icon" class="size-[19px]" :stroke-width="isActive(item.href) ? 2.2 : 1.7" />
            </span>
            {{ t('nav.' + item.key) }}
            <span v-if="isActive(item.href)" class="absolute top-0 h-[3px] w-7 rounded-b-full bg-gold-500" />
          </Link>
        </li>
      </ul>
    </nav>
  </div>
</template>
