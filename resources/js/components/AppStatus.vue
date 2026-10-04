<!-- ===== QURBA: offline notice, 7-day sync reminder, install prompt ===== -->
<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { watchEffect } from 'vue';
import { useI18n } from 'vue-i18n';
import { CloudOff, Download, RefreshCw, Share, X } from 'lucide-vue-next';
import { app, dismissInstall, install, isIos, laterSync, showInstall, syncNow, syncOverdue } from '../lib/pwa';

const { t } = useI18n();
const page = usePage();
watchEffect(() => { (window as any).__qurbaInertiaVersion = page.version ?? ''; (window as any).__qurbaUser = (page.props as any).auth?.user ?? null; });
</script>

<template>
  <div class="space-y-3 empty:hidden">
    <p v-if="!app.online" class="flex items-center gap-2 rounded-2xl bg-ink px-4 py-2.5 text-sm text-cream">
      <CloudOff class="size-4 shrink-0" /> {{ t('pwa.offline') }}
    </p>

    <div v-if="syncOverdue" role="status" class="rounded-2xl border border-gold-500 bg-paper px-4 py-3 text-sm">
      <p>{{ t('pwa.overdue') }}</p>
      <div class="mt-3 flex gap-2">
        <button class="inline-flex items-center gap-2 rounded-full bg-emerald-900 px-4 py-1.5 text-cream disabled:opacity-50" :disabled="app.syncing || !app.online" @click="syncNow">
          <RefreshCw class="size-4" :class="app.syncing ? 'animate-spin' : ''" /> {{ t('pwa.syncNow') }}
        </button>
        <button class="rounded-full px-4 py-1.5 text-ink-soft" @click="laterSync">{{ t('pwa.later') }}</button>
      </div>
    </div>

    <div v-if="showInstall" class="flex items-start gap-3 rounded-2xl pattern-light bg-gradient-to-br from-emerald-700 to-emerald-500 shadow-lift px-4 py-3 text-sm text-cream">
      <img src="/brand/qurba-icon-96.png" alt="" class="size-10 shrink-0" />
      <div class="min-w-0 flex-1">
        <p class="font-medium">{{ t('pwa.installTitle') }}</p>
        <p v-if="app.installEvent" class="text-gold-200">{{ t('pwa.installText') }}</p>
        <p v-else-if="isIos" class="flex flex-wrap items-center gap-1 text-gold-200">{{ t('pwa.iosA') }} <Share class="inline size-4" /> {{ t('pwa.iosB') }}</p>
        <button v-if="app.installEvent" class="mt-2 inline-flex items-center gap-2 rounded-full bg-gold-500 px-4 py-1.5 text-emerald-950" @click="install"><Download class="size-4" /> {{ t('pwa.install') }}</button>
      </div>
      <button class="grid size-7 place-items-center rounded-full text-gold-200" @click="dismissInstall" :aria-label="t('pwa.dismiss')"><X class="size-4" /></button>
    </div>
  </div>
</template>
