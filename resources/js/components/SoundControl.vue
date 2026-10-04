<!-- ===== QURBA: header sound control (background sound + adhan) ===== -->
<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Music2, Pause, Play, Volume2, VolumeX } from 'lucide-vue-next';
import { FILES, hasFile, playAdhan, sound, soundPrefs, stopOneShot, toggleAmbient } from '../lib/sounds';

const { t } = useI18n();
const open = ref(false);
const root = ref<HTMLElement | null>(null);
const adhanAvailable = ref<boolean | null>(null);
onMounted(async () => {
  document.addEventListener('pointerdown', outside);
  sound.ambientAvailable = await hasFile(FILES.ambient);
  adhanAvailable.value = await hasFile(FILES.adhan);
});
onBeforeUnmount(() => document.removeEventListener('pointerdown', outside));
function outside(e: PointerEvent) { if (open.value && root.value && !root.value.contains(e.target as Node)) open.value = false; }
</script>

<template>
  <div ref="root" class="relative">
    <button class="grid size-9 place-items-center rounded-full border border-line bg-paper text-emerald-900" :aria-expanded="open"
      :aria-label="t('sound.title')" :title="t('sound.title')" @click="open = !open">
      <VolumeX v-if="soundPrefs.muted" class="size-4" />
      <Music2 v-else class="size-4" :class="sound.ambientPlaying ? 'animate-pulse' : ''" />
    </button>
    <div v-if="open" class="absolute end-0 top-full z-40 mt-2 w-72 rounded-2xl border border-line bg-paper p-4 text-sm shadow-lg">
      <p class="font-medium text-ink">{{ t('sound.title') }}</p>

      <div class="mt-3 flex items-center gap-2">
        <button class="grid size-9 place-items-center rounded-full bg-emerald-900 text-cream disabled:opacity-40"
          :disabled="sound.ambientAvailable === false" :aria-label="sound.ambientPlaying ? t('sound.pause') : t('sound.play')" @click="toggleAmbient">
          <Pause v-if="sound.ambientPlaying" class="size-4" /><Play v-else class="size-4" />
        </button>
        <span class="flex-1 text-ink">{{ t('sound.background') }}</span>
        <button class="grid size-8 place-items-center rounded-full border border-line" :aria-pressed="soundPrefs.muted"
          :aria-label="soundPrefs.muted ? t('sound.unmute') : t('sound.mute')" @click="soundPrefs.muted = !soundPrefs.muted">
          <VolumeX v-if="soundPrefs.muted" class="size-4" /><Volume2 v-else class="size-4" />
        </button>
      </div>
      <input v-model.number="soundPrefs.volume" type="range" min="0" max="0.6" step="0.05" :aria-label="t('sound.volume')"
        class="mt-3 w-full accent-emerald-900" :disabled="soundPrefs.muted" />
      <p v-if="sound.ambientAvailable === false" class="mt-2 text-xs text-ink-soft">{{ t('sound.missing') }}</p>

      <div class="mt-4 border-t border-line pt-3">
        <label class="flex items-center gap-2 text-ink">
          <input v-model="soundPrefs.adhanOn" type="checkbox" class="rounded text-emerald-900 focus:ring-gold-500" /> {{ t('sound.adhanOn') }}
        </label>
        <div class="mt-2 flex items-center gap-2 text-xs">
          <button v-if="!sound.adhanPlaying" class="rounded-full border border-line px-3 py-1 disabled:opacity-40" :disabled="adhanAvailable === false"
            @click="playAdhan('dhuhr')">{{ t('sound.testAdhan') }}</button>
          <button v-else class="rounded-full bg-emerald-900 px-3 py-1 text-cream" @click="stopOneShot">{{ t('sound.stopAdhan') }}</button>
          <span v-if="adhanAvailable === false" class="text-ink-soft">{{ t('sound.adhanMissing') }}</span>
        </div>
        <p class="mt-2 text-[11px] leading-snug text-ink-soft">{{ t('sound.adhanNote') }}</p>
      </div>
    </div>
  </div>
</template>
