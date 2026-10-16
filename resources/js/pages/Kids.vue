<!-- ===== QURBA: Kids corner — learn short surahs ayah by ayah, a star for each one, per child ===== -->
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { BookOpen, HandHeart, Languages, ScrollText, Pause, Play, Plus, SkipBack, SkipForward, Sparkles, Square, Star, Trash2, X } from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import QurbaShell from '../layouts/QurbaShell.vue';
import { activeChild, addChild, kids, removeChild, renameChild, toggleStar } from '../lib/kids';
import { audioPrefs, drill, next, player, playQueue, prev, queue, setReciter, stop, toggle, type Reciter } from '../lib/quranAudio';

interface Surah { id: number; name_simple: string; name_arabic: string | null; name_english: string | null; ayah_count: number }
const props = defineProps<{ surahs: Surah[]; reciters: Reciter[] }>();
const { t } = useI18n();
const name = (id: number) => props.surahs.find((s) => s.id === id)?.name_simple ?? '';

const reciter = computed(() => props.reciters.find((r) => r.id === audioPrefs.reciterId) ?? props.reciters[0] ?? null);
watch(reciter, (r) => setReciter(r), { immediate: true });
// The repeat-each-ayah drill is only for this page
onBeforeUnmount(() => { drill.times = 1; });

const child = computed(() => activeChild());
const childName = (i: number) => kids.children[i].name || t('kids.child', { n: i + 1 });
const cheer = ref('');
function learned(id: number) {
  if (!toggleStar(id)) return;
  cheer.value = t('kids.cheer'); setTimeout(() => (cheer.value = ''), 2500);
  if ('vibrate' in navigator) navigator.vibrate?.([20, 40, 20]);
}

// Adding / renaming children
const newName = ref('');
const adding = ref(false);
function saveChild() { if (newName.value.trim()) addChild(newName.value); newName.value = ''; adding.value = false; }

// Learn panel: the surah text with the ayah being recited highlighted
const learning = ref<{ surah: number; bismillah: string | null; ayahs: { n: number; text: string }[] } | null>(null);
const textCache = new Map<number, NonNullable<typeof learning.value>>();
const failed = ref(false);
const key = (id: number) => `kids-${id}`;
const playingThis = (id: number) => queue.index >= 0 && queue.key === key(id);

async function learn(id: number, autoplay = true) {
  failed.value = false;
  try {
    let data = textCache.get(id);
    if (!data) {
      const res = await fetch(`/api/v1/quran/surahs/${id}`, { headers: { Accept: 'application/json' } });
      if (!res.ok) throw new Error(String(res.status));
      const json = (await res.json()).data;
      data = { surah: id, bismillah: json.bismillah, ayahs: json.ayahs.map((a: { n: number; text: string }) => ({ n: a.n, text: a.text })) };
      textCache.set(id, data);
    }
    learning.value = data;
    await nextTick();
    document.getElementById('learn')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  } catch { failed.value = true; }
  if (autoplay && reciter.value && !playingThis(id)) playQueue(key(id), [id]);
}
function close() { if (learning.value && playingThis(learning.value.surah)) stop(); learning.value = null; }
const current = (n: number) => learning.value !== null && playingThis(learning.value.surah) && player.ayah === n && !player.inBismillah;

const tones = ['bg-teal-100', 'bg-lime-100', 'bg-olive-100', 'bg-leaf-100', 'bg-gold-200/60'];
const links = [
  { href: '/kids/qaida', key: 'kids.qaida', icon: Languages },
  { href: '/zikr/adhkar/kalimas', key: 'kids.kalimas', icon: ScrollText },
  { href: '/library/kids', key: 'kids.stories', icon: BookOpen },
  { href: '/names', key: 'kids.names', icon: Sparkles },
  { href: '/zikr/adhkar/daily-duas', key: 'kids.duas', icon: HandHeart },
];
</script>

<template>
  <Head :title="t('kids.title')" />
  <QurbaShell>
    <section class="relative overflow-hidden rounded-[var(--radius-sheet)] bg-gradient-to-br from-lime-100 via-paper to-teal-100 p-6 shadow-soft md:p-10">
      <p dir="rtl" lang="ar" class="w-fit font-quran text-2xl text-gold-600">رَبِّ زِدْنِي عِلْمًا</p>
      <h1 class="mt-1 font-display text-3xl text-emerald-900 md:text-4xl">{{ t('kids.title') }}</h1>
      <p class="mt-2 max-w-lg text-ink-soft">{{ t('kids.sub') }}</p>

      <!-- Who is learning -->
      <div class="mt-5 flex flex-wrap items-center gap-2" role="radiogroup" :aria-label="t('kids.who')">
        <button v-for="(c, i) in kids.children" :key="c.id" type="button" role="radio" :aria-checked="kids.active === c.id"
          class="inline-flex items-center gap-1.5 rounded-full px-4 py-2 text-sm transition-colors"
          :class="kids.active === c.id ? 'bg-emerald-900 text-cream' : 'bg-paper text-ink shadow-soft hover:bg-cream'" @click="kids.active = c.id">
          <Star class="size-4" :class="kids.active === c.id ? 'fill-gold-500 text-gold-500' : 'text-gold-600'" /> {{ childName(i) }} · {{ c.stars.length }}
        </button>
        <form v-if="adding" class="flex items-center gap-1" @submit.prevent="saveChild">
          <input v-model="newName" maxlength="30" :placeholder="t('kids.namePh')" :aria-label="t('kids.namePh')" class="w-36 rounded-full border-line bg-paper py-1.5 text-sm focus:border-gold-500 focus:ring-0" />
          <button type="submit" class="rounded-full bg-gold-500 px-3 py-1.5 text-sm text-emerald-950">{{ t('kids.save') }}</button>
        </form>
        <button v-else type="button" class="inline-flex items-center gap-1 rounded-full border border-dashed border-emerald-700/40 px-3 py-2 text-sm text-emerald-900" @click="adding = true">
          <Plus class="size-4" /> {{ t('kids.addChild') }}
        </button>
      </div>

      <div v-if="child" class="mt-4 inline-flex items-center gap-3 rounded-full bg-paper px-5 py-2.5 shadow-soft">
        <Star class="size-6 fill-gold-500 text-gold-500" />
        <span class="font-display text-2xl text-emerald-900">{{ child.stars.length }}</span>
        <span class="text-sm text-ink-soft">{{ t('kids.starsOf', { n: surahs.length }) }}</span>
      </div>
      <p v-if="cheer" role="status" class="mt-3 font-display text-xl text-gold-600">{{ cheer }}</p>
    </section>

    <nav class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
      <Link v-for="l in links" :key="l.href" :href="l.href" class="flex flex-col items-center gap-2 rounded-[var(--radius-tile)] bg-paper px-2 py-4 text-center shadow-soft hover:shadow-lift">
        <span class="grid size-12 place-items-center rounded-2xl bg-teal-100"><component :is="l.icon" class="size-6 text-emerald-700" /></span>
        <span class="text-sm font-medium text-ink">{{ t(l.key) }}</span>
      </Link>
    </nav>

    <!-- Learn ayah by ayah -->
    <section v-if="learning" id="learn" class="mt-6 scroll-mt-24 rounded-[var(--radius-sheet)] bg-paper p-5 shadow-lift md:p-6">
      <div class="flex flex-wrap items-center gap-3">
        <h2 class="me-auto font-display text-2xl text-emerald-900">{{ name(learning.surah) }}</h2>
        <button type="button" class="grid size-9 place-items-center rounded-full border border-line" :aria-label="t('kids.close')" @click="close"><X class="size-4" /></button>
      </div>
      <div v-if="reciter" class="mt-3 flex flex-wrap items-center gap-2">
        <button type="button" class="grid size-10 place-items-center rounded-full border border-line" :aria-label="t('playlists.prev')" @click="prev"><SkipBack class="size-4" /></button>
        <button type="button" class="grid size-12 place-items-center rounded-full bg-emerald-900 text-cream" :aria-label="playingThis(learning.surah) && player.playing ? t('sound.pause') : t('sound.play')"
          @click="playingThis(learning.surah) ? toggle() : learn(learning.surah)">
          <Pause v-if="playingThis(learning.surah) && player.playing" class="size-5" /><Play v-else class="size-5" />
        </button>
        <button type="button" class="grid size-10 place-items-center rounded-full border border-line" :aria-label="t('playlists.next')" @click="next"><SkipForward class="size-4" /></button>
        <button type="button" class="grid size-10 place-items-center rounded-full border border-line" :aria-label="t('playlists.stop')" @click="stop"><Square class="size-4" /></button>
        <span class="ms-auto text-sm text-ink-soft">{{ t('kids.repeat') }}</span>
        <button v-for="n in [1, 3, 5]" :key="n" type="button" class="rounded-full px-3 py-1.5 text-sm" :aria-pressed="drill.times === n"
          :class="drill.times === n ? 'bg-gold-500 text-emerald-950' : 'border border-line text-ink'" @click="drill.times = n">{{ n }}×</button>
      </div>
      <p v-if="drill.times > 1 && learning && playingThis(learning.surah)" class="mt-2 text-xs text-ink-soft">{{ t('kids.repeatNow', { a: drill.done + 1, b: drill.times }) }}</p>
      <p v-if="learning.bismillah" dir="rtl" lang="ar" class="mt-4 text-center font-quran text-2xl text-emerald-900">{{ learning.bismillah }}</p>
      <ol class="mt-3 space-y-2">
        <li v-for="a in learning.ayahs" :key="a.n" class="flex items-start gap-3 rounded-2xl p-3 transition-colors" :class="current(a.n) ? 'bg-gold-200/70' : ''">
          <span class="mt-2 grid size-8 shrink-0 place-items-center rounded-full border border-gold-500 text-xs text-gold-600">{{ a.n }}</span>
          <p dir="rtl" lang="ar" class="flex-1 font-quran text-3xl leading-[2.2] text-ink">{{ a.text }}</p>
        </li>
      </ol>
      <button type="button" class="mt-4 inline-flex items-center gap-2 rounded-full px-5 py-2.5 text-sm"
        :class="child?.stars.includes(learning.surah) ? 'bg-gold-200 text-emerald-950' : 'bg-gold-500 text-emerald-950'" @click="learned(learning.surah)">
        <Star class="size-4 fill-current" /> {{ t('kids.learned') }}
      </button>
    </section>
    <p v-if="failed" role="alert" class="mt-4 text-sm text-ink-soft">{{ t('daily.error') }}</p>

    <h2 class="mt-8 font-display text-2xl text-emerald-900">{{ t('kids.learn') }}</h2>
    <ul class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <li v-for="(s, i) in surahs" :key="s.id" class="flex flex-col rounded-[var(--radius-tile)] bg-paper p-4 shadow-soft">
        <div class="flex items-center gap-3">
          <span class="grid size-12 shrink-0 place-items-center rounded-2xl font-display text-lg text-emerald-900" :class="tones[i % tones.length]">{{ s.id }}</span>
          <span class="min-w-0 flex-1">
            <span class="block font-medium text-ink">{{ s.name_simple }}</span>
            <span class="text-xs text-ink-soft">{{ t('kids.ayahs', { n: s.ayah_count }) }}</span>
          </span>
          <span v-if="s.name_arabic" dir="rtl" lang="ar" class="font-quran text-xl text-emerald-900">{{ s.name_arabic }}</span>
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
          <button type="button" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-900 px-4 py-2 text-sm text-cream" @click="learn(s.id)">
            <Play class="size-4" /> {{ reciter ? t('kids.listen') : t('kids.read') }}
          </button>
          <button type="button" class="ms-auto inline-flex items-center gap-1.5 rounded-full px-3 py-2 text-sm transition-colors" :aria-pressed="child?.stars.includes(s.id)"
            :class="child?.stars.includes(s.id) ? 'bg-gold-200 text-emerald-950' : 'border border-line text-ink-soft hover:border-gold-500'" @click="learned(s.id)">
            <Star class="size-4" :class="child?.stars.includes(s.id) ? 'fill-gold-500 text-gold-600' : ''" /> {{ t('kids.learned') }}
          </button>
        </div>
      </li>
    </ul>

    <!-- Parent view -->
    <section class="mt-10 rounded-[var(--radius-tile)] bg-paper p-5 shadow-soft">
      <h2 class="font-display text-xl text-emerald-900">{{ t('kids.parent') }}</h2>
      <p class="mt-1 text-sm text-ink-soft">{{ t('kids.parentSub') }}</p>
      <ul class="mt-4 space-y-4">
        <li v-for="(c, i) in kids.children" :key="c.id" class="rounded-2xl border border-line p-4">
          <div class="flex flex-wrap items-center gap-3">
            <input :value="c.name" maxlength="30" :placeholder="childName(i)" :aria-label="t('kids.namePh')"
              class="min-w-0 flex-1 rounded-full border-line bg-cream py-1.5 text-sm focus:border-gold-500 focus:ring-0" @change="renameChild(c.id, ($event.target as HTMLInputElement).value)" />
            <span class="inline-flex items-center gap-1 text-sm text-emerald-900"><Star class="size-4 fill-gold-500 text-gold-500" /> {{ c.stars.length }} / {{ surahs.length }}</span>
            <button v-if="kids.children.length > 1" type="button" class="grid size-8 place-items-center rounded-full text-ink-soft hover:bg-cream" :aria-label="t('kids.removeChild')"
              @click="removeChild(c.id)"><Trash2 class="size-4" /></button>
          </div>
          <div class="mt-3 h-2 overflow-hidden rounded-full bg-line"><div class="h-full rounded-full bg-gold-500" :style="{ width: (c.stars.length / surahs.length) * 100 + '%' }" /></div>
          <p class="mt-2 text-xs text-ink-soft">{{ c.stars.length ? c.stars.map(name).filter(Boolean).join(' · ') : t('kids.noStars') }}</p>
        </li>
      </ul>
    </section>
  </QurbaShell>
</template>
