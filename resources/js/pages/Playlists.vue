<!-- ===== QURBA: Quran audio playlists (surahs played back to back) ===== -->
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ListMusic, Moon, Pause, Play, Plus, Shield, SkipBack, SkipForward, Square, Sun, Trash2, Sparkles, BookOpen } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import PageTitle from '../components/PageTitle.vue';
import QurbaShell from '../layouts/QurbaShell.vue';
import { audioPrefs, next, player, playQueue, prev, queue, setReciter, setSleep, stop, toggle, type Reciter } from '../lib/quranAudio';

const props = defineProps<{ surahs: { id: number; name_simple: string }[]; reciters: Reciter[] }>();
const { t } = useI18n();
const name = (id: number) => props.surahs.find((s) => s.id === id)?.name_simple ?? `Surah ${id}`;

const reciter = computed(() => props.reciters.find((r) => r.id === audioPrefs.reciterId) ?? props.reciters[0] ?? null);
watch(reciter, (r) => { setReciter(r); if (r) audioPrefs.reciterId = r.id; }, { immediate: true });

const PRESETS = [
  { key: 'night', icon: Moon, surahs: [67, 32] },
  { key: 'friday', icon: BookOpen, surahs: [18] },
  { key: 'morning', icon: Sun, surahs: [36, 55, 56] },
  { key: 'protection', icon: Shield, surahs: [1, 112, 113, 114] },
  { key: 'short', icon: Sparkles, surahs: Array.from({ length: 22 }, (_, i) => 93 + i) },
];

// "My playlist", kept on this device
const KEY = 'qurba.playlist';
const mine = ref<number[]>((() => { try { return JSON.parse(localStorage.getItem(KEY) || '[]'); } catch { return []; } })());
watch(mine, (v) => { try { localStorage.setItem(KEY, JSON.stringify(v)); } catch { /* storage unavailable */ } }, { deep: true });
const pick = ref(1);
function add() { if (!mine.value.includes(pick.value)) mine.value.push(pick.value); }
const remove = (id: number) => (mine.value = mine.value.filter((x) => x !== id));

const active = (key: string) => queue.index >= 0 && queue.key === key;
function play(key: string, surahs: number[]) { if (active(key)) toggle(); else playQueue(key, surahs); }
const sleeps = [0, 15, 30, 60];
const sleepPick = ref(0);
function sleep(m: number) { sleepPick.value = m; setSleep(m); }
</script>

<template>
  <Head :title="t('playlists.title')" />
  <QurbaShell>
    <PageTitle :title="t('playlists.title')" arabic="قوائم التلاوة" :sub="t('playlists.sub')" />

    <p v-if="!reciter" class="mt-6 rounded-[var(--radius-tile)] bg-paper p-5 text-ink-soft shadow-soft">{{ t('playlists.noAudio') }}</p>

    <template v-else>
      <!-- Now playing -->
      <section v-if="queue.index >= 0" class="sticky top-20 z-20 mt-6 rounded-[var(--radius-tile)] bg-gradient-to-br from-teal-950 via-teal-900 to-teal-700 p-5 text-cream shadow-lift md:top-24">
        <div class="flex flex-wrap items-center gap-4">
          <div class="min-w-0 flex-1">
            <p class="text-xs text-gold-200">{{ t('playlists.' + queue.key) }} · {{ queue.index + 1 }}/{{ queue.surahs.length }}</p>
            <p class="truncate font-display text-xl">{{ name(player.surah) }} <span class="text-sm text-gold-200">· {{ t('quran.ayah') }} {{ player.ayah }}/{{ player.total }}</span></p>
          </div>
          <div class="flex items-center gap-2">
            <button type="button" class="grid size-10 place-items-center rounded-full bg-white/10" :aria-label="t('playlists.prev')" @click="prev"><SkipBack class="size-4" /></button>
            <button type="button" class="grid size-12 place-items-center rounded-full bg-gold-500 text-emerald-950" :aria-label="player.playing ? t('sound.pause') : t('sound.play')" @click="toggle">
              <Pause v-if="player.playing" class="size-5" /><Play v-else class="size-5" />
            </button>
            <button type="button" class="grid size-10 place-items-center rounded-full bg-white/10" :aria-label="t('playlists.next')" @click="next"><SkipForward class="size-4" /></button>
            <button type="button" class="grid size-10 place-items-center rounded-full bg-white/10" :aria-label="t('playlists.stop')" @click="stop"><Square class="size-4" /></button>
          </div>
        </div>
        <div class="mt-3 h-1 overflow-hidden rounded-full bg-white/15"><div class="h-full bg-gold-500" :style="{ width: player.progress * 100 + '%' }" /></div>
        <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-gold-200">
          <span>{{ t('playlists.sleep') }}</span>
          <button v-for="m in sleeps" :key="m" type="button" class="rounded-full px-2.5 py-1"
            :class="(m === 0 ? !player.sleepAt : sleepPick === m && player.sleepAt) ? 'bg-white/20 text-white' : 'bg-white/5 hover:bg-white/10'" @click="sleep(m)">
            {{ m ? t('playlists.min', { n: m }) : t('playlists.off') }}
          </button>
        </div>
        <p v-if="player.error" class="mt-2 text-xs text-gold-200">{{ t('playlists.error') }}</p>
      </section>

      <!-- Reciter -->
      <label class="mt-6 flex items-center gap-3 text-sm text-ink-soft">{{ t('playlists.reciter') }}
        <select v-model.number="audioPrefs.reciterId" class="rounded-full border-line bg-paper py-1.5 pe-9 ps-4 text-sm text-ink">
          <option v-for="r in reciters" :key="r.id" :value="r.id">{{ r.name }}</option>
        </select>
      </label>

      <!-- Ready-made playlists -->
      <ul class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <li v-for="p in PRESETS" :key="p.key" class="flex flex-col rounded-[var(--radius-tile)] bg-paper p-5 shadow-soft" :class="active(p.key) ? 'ring-2 ring-gold-500' : ''">
          <div class="flex items-start gap-3">
            <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-teal-100"><component :is="p.icon" class="size-5 text-emerald-700" /></span>
            <div class="min-w-0 flex-1">
              <h2 class="font-display text-lg text-emerald-900">{{ t('playlists.' + p.key) }}</h2>
              <p class="text-xs text-ink-soft">{{ t('playlists.d_' + p.key) }}</p>
            </div>
          </div>
          <p class="mt-3 flex-1 text-sm text-ink">{{ p.surahs.length > 4 ? `${name(p.surahs[0])} – ${name(p.surahs[p.surahs.length - 1])}` : p.surahs.map(name).join(' · ') }}</p>
          <button type="button" class="mt-4 inline-flex w-fit items-center gap-2 rounded-full bg-emerald-900 px-5 py-2 text-sm text-cream hover:bg-emerald-700" @click="play(p.key, p.surahs)">
            <Pause v-if="active(p.key) && player.playing" class="size-4" /><Play v-else class="size-4" />
            {{ active(p.key) && player.playing ? t('sound.pause') : t('playlists.play') }}
          </button>
        </li>
      </ul>

      <!-- My playlist -->
      <section class="mt-6 rounded-[var(--radius-tile)] bg-paper p-5 shadow-soft" :class="active('mine') ? 'ring-2 ring-gold-500' : ''">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <h2 class="flex items-center gap-2 font-display text-lg text-emerald-900"><ListMusic class="size-5 text-emerald-700" /> {{ t('playlists.mine') }}</h2>
          <button v-if="mine.length" type="button" class="inline-flex items-center gap-2 rounded-full bg-emerald-900 px-5 py-2 text-sm text-cream hover:bg-emerald-700" @click="play('mine', mine)">
            <Pause v-if="active('mine') && player.playing" class="size-4" /><Play v-else class="size-4" />
            {{ active('mine') && player.playing ? t('sound.pause') : t('playlists.play') }}
          </button>
        </div>
        <form class="mt-3 flex flex-wrap gap-2" @submit.prevent="add">
          <select v-model.number="pick" :aria-label="t('playlists.choose')" class="min-w-0 flex-1 rounded-full border-line bg-cream py-2 pe-9 ps-4 text-sm">
            <option v-for="s in surahs" :key="s.id" :value="s.id">{{ s.id }}. {{ s.name_simple }}</option>
          </select>
          <button type="submit" class="inline-flex items-center gap-1.5 rounded-full border border-line px-4 py-2 text-sm text-emerald-900 hover:border-gold-500"><Plus class="size-4" /> {{ t('playlists.add') }}</button>
        </form>
        <p v-if="!mine.length" class="mt-3 text-sm text-ink-soft">{{ t('playlists.empty') }}</p>
        <ol class="mt-3 divide-y divide-line">
          <li v-for="(id, i) in mine" :key="id" class="flex items-center gap-3 py-2 text-sm">
            <span class="w-6 text-ink-soft">{{ i + 1 }}</span>
            <Link :href="`/quran/${id}`" class="flex-1 text-ink hover:text-emerald-900">{{ name(id) }}</Link>
            <button type="button" class="grid size-8 place-items-center rounded-full text-ink-soft hover:bg-cream" :aria-label="t('playlists.remove')" @click="remove(id)"><Trash2 class="size-4" /></button>
          </li>
        </ol>
      </section>
    </template>
  </QurbaShell>
</template>
