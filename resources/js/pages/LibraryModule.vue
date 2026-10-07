<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import QurbaShell from '../layouts/QurbaShell.vue';
import { useLocale } from '../composables/useLocale';

const props = defineProps<{ module: string; title: string }>();
const { locale } = useLocale();
type Text = Record<string, string>;
interface Entry { slug: string; title: Text; summary?: Text; body?: Text; reference?: string; grading?: string;
  source?: { name: string; author?: string };
  audio?: { language: string; speaker: string; url: string }[]; }
const entries = ref<Entry[]>([]);
const selected = ref<Entry | null>(null);
const loading = ref(false);
const error = ref('');
const nextPage = ref<string | null>(null);
const search = ref('');
const localText = (value?: Text) => value?.[locale.value] || value?.en || value?.ar || value?.ur || '';
const visible = computed(() => entries.value.filter(item => (localText(item.title) + ' ' + localText(item.summary)).toLowerCase().includes(search.value.toLowerCase())));
async function load(url = '/api/v1/library/' + props.module) {
  loading.value = true; error.value = '';
  try {
    const response = await fetch(url, { headers: { Accept: 'application/json' } });
    if (!response.ok) throw new Error();
    const result = await response.json();
    entries.value.push(...result.data); nextPage.value = result.next_page_url;
  } catch { error.value = 'Unable to load this section. Please try again.'; }
  finally { loading.value = false; }
}
async function open(item: Entry) {
  loading.value = true; error.value = '';
  try {
    const response = await fetch('/api/v1/library/' + props.module + '/' + encodeURIComponent(item.slug), { headers: { Accept: 'application/json' } });
    if (!response.ok) throw new Error();
    selected.value = (await response.json()).data;
  } catch { error.value = 'Unable to open this item. Please try again.'; }
  finally { loading.value = false; }
}
onMounted(() => load());
</script>

<template>
  <Head :title="title" />
  <QurbaShell>
    <h1 class="mb-6 text-3xl font-semibold text-emerald-900">{{ title }}</h1>
    <p v-if="loading" role="status" class="mb-4 text-ink-soft">Loading…</p>
    <div v-if="error" role="alert" class="mb-4 rounded-xl bg-red-50 p-4">
      {{ error }} <button class="ms-2 underline" @click="selected ? open(selected) : load(nextPage || undefined)">Retry</button>
    </div>
    <section v-if="selected" class="rounded-2xl bg-paper p-6 md:p-8">
      <button class="mb-5 text-emerald-900 underline" @click="selected = null">Back to list</button>
      <h2 class="text-2xl font-semibold">{{ localText(selected.title) }}</h2>
      <p class="mt-5 whitespace-pre-line text-lg leading-loose" :dir="locale === 'en' ? 'ltr' : 'rtl'">{{ localText(selected.body) }}</p>
      <p v-if="selected.reference" class="mt-6 text-sm text-ink-soft">Reference: {{ selected.reference }}</p>
      <p v-if="selected.grading" class="mt-2 text-sm text-ink-soft">Grading: {{ selected.grading }}</p>
      <p v-if="selected.source" class="mt-2 text-sm text-ink-soft">Source: {{ selected.source.name }} {{ selected.source.author }}</p>
      <div v-for="audio in selected.audio" :key="audio.url" class="mt-5">
        <p class="mb-2 text-sm">{{ audio.speaker }} · {{ audio.language }}</p>
        <audio controls preload="none" :src="audio.url" class="w-full" />
      </div>
    </section>
    <template v-else>
      <label class="mb-5 block">
        <span class="mb-2 block text-sm">Search loaded items</span>
        <input v-model="search" type="search" class="w-full rounded-xl border-line bg-paper" />
      </label>
      <div class="grid gap-4 md:grid-cols-2">
        <button v-for="item in visible" :key="item.slug" class="rounded-2xl bg-paper p-5 text-start" @click="open(item)">
          <h2 class="text-xl font-medium">{{ localText(item.title) }}</h2>
          <p class="mt-2 text-ink-soft">{{ localText(item.summary) }}</p>
          <p v-if="item.reference" class="mt-3 text-sm text-ink-soft">{{ item.reference }}</p>
        </button>
      </div>
      <p v-if="!loading && !error && !entries.length" class="rounded-2xl bg-paper p-6 text-ink-soft">No published content is available in this section yet.</p>
      <button v-if="nextPage" class="mt-5 rounded-full bg-emerald-900 px-5 py-2 text-white" :disabled="loading" @click="load(nextPage)">Load more</button>
    </template>
  </QurbaShell>
</template>
