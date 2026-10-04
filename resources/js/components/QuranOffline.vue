<!-- ===== QURBA: download the whole Quran text for offline reading ===== -->
<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { CheckCircle2, CloudDownload } from 'lucide-vue-next';
import { app, persistStorage, warmUp } from '../lib/pwa';

const { t } = useI18n();
const read = () => { try { return JSON.parse(localStorage.getItem('qurba.offlineQuran') || 'null'); } catch { return null; } };
const saved = ref<{ at: number } | null>(read());
const progress = ref(0);
const busy = ref(false);

async function download() {
  busy.value = true; progress.value = 0;
  await persistStorage();
  const paths = ['/quran', ...Array.from({ length: 114 }, (_, i) => `/quran/${i + 1}`)];
  await warmUp(paths, (d) => (progress.value = Math.round((d / paths.length) * 100)));
  saved.value = { at: Date.now() };
  try { localStorage.setItem('qurba.offlineQuran', JSON.stringify(saved.value)); } catch {}
  busy.value = false;
}
</script>

<template>
  <div class="flex flex-wrap items-center gap-3 rounded-[var(--radius-tile)] bg-paper shadow-soft px-5 py-4 text-sm">
    <CheckCircle2 v-if="saved && !busy" class="size-5 text-emerald-700" />
    <CloudDownload v-else class="size-5 text-emerald-700" />
    <div class="min-w-0 flex-1">
      <p class="font-medium text-ink">{{ saved && !busy ? t('pwa.quranSaved') : t('pwa.quranOffline') }}</p>
      <p class="text-xs text-ink-soft">{{ !app.swReady ? t('pwa.needsBuild') : busy ? `${progress}%` : t('pwa.quranHint') }}</p>
      <div v-if="busy" class="mt-2 h-1.5 rounded-full bg-line"><div class="h-1.5 rounded-full bg-gold-500" :style="{ width: progress + '%' }" /></div>
    </div>
    <button class="rounded-full bg-emerald-900 px-4 py-1.5 text-cream disabled:opacity-50" :disabled="busy || !app.swReady || !app.online" @click="download">
      {{ saved ? t('pwa.update') : t('pwa.download') }}
    </button>
  </div>
</template>
