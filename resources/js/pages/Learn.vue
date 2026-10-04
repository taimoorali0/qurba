<!-- ===== QURBA Learning: course catalogue + register interest ===== -->
<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { CheckCircle2, GraduationCap, ShieldCheck, Users, User, X } from 'lucide-vue-next';
import QurbaShell from '../layouts/QurbaShell.vue';
import { loc } from '../lib/location';

interface Course { id: number; slug: string; title: Record<string, string>; summary: Record<string, string> | null; audience: string; format: string; category: string; min_age: string | null }
const props = defineProps<{ courses: Course[] }>();
const { t, locale } = useI18n();
const page = usePage<any>();
const tr = (o: Record<string, string> | null) => (o ? o[locale.value] || o.en : '');

const filters = ['all', 'kids', 'adults', 'tajweed', 'hifz', 'arabic'] as const;
const filter = ref<typeof filters[number]>('all');
const shown = computed(() => props.courses.filter((c) =>
  filter.value === 'all' || c.audience === filter.value || c.category === filter.value));

const open = ref<Course | null>(null);
const sent = ref(false);
const user = computed(() => page.props.auth?.user ?? null);
const form = useForm({
  course_id: 0, name: '', email: '', phone: '', country: '', language: 'en', format: 'either',
  for_child: false, child_age_range: '', message: '', adult_confirm: false, contact_consent: false, website: '',
});
function start(c: Course) {
  open.value = c; sent.value = false; form.clearErrors();
  form.course_id = c.id; form.language = locale.value; form.country = loc.place?.country ?? '';
  form.for_child = c.audience === 'kids';
  if (user.value) { form.name ||= user.value.name; form.email ||= user.value.email; }
}
function submit() { form.post('/learn/interest', { preserveScroll: true, onSuccess: () => { sent.value = true; form.reset('message'); } }); }
</script>

<template>
  <Head :title="t('learn.title')" />
  <QurbaShell>
    <section class="rounded-[var(--radius-sheet)] bg-emerald-900 p-6 text-cream md:p-10">
      <GraduationCap class="size-8 text-gold-500" />
      <h1 class="mt-4 font-display text-3xl md:text-5xl">{{ t('learn.title') }}</h1>
      <p class="mt-3 max-w-xl text-gold-200">{{ t('learn.intro') }}</p>
      <ul class="mt-6 flex flex-wrap gap-x-6 gap-y-2 text-sm text-cream/90">
        <li class="flex items-center gap-2"><ShieldCheck class="size-4 text-gold-500" /> {{ t('learn.verified') }}</li>
        <li class="flex items-center gap-2"><User class="size-4 text-gold-500" /> {{ t('learn.oneToOne') }}</li>
        <li class="flex items-center gap-2"><Users class="size-4 text-gold-500" /> {{ t('learn.group') }}</li>
      </ul>
    </section>

    <div class="mt-6 flex flex-wrap gap-2 text-sm">
      <button v-for="f in filters" :key="f" class="rounded-full px-4 py-1.5" :class="filter === f ? 'bg-emerald-900 text-cream' : 'border border-line bg-paper'" @click="filter = f">{{ t('learn.f_' + f) }}</button>
    </div>

    <ul class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <li v-for="c in shown" :key="c.id" class="flex flex-col rounded-[var(--radius-sheet)] border border-line bg-paper p-5">
        <h2 class="font-display text-xl text-emerald-900">{{ tr(c.title) }}</h2>
        <p class="mt-2 flex-1 text-sm text-ink-soft">{{ tr(c.summary) }}</p>
        <div class="mt-4 flex flex-wrap gap-2 text-xs">
          <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-emerald-900">{{ t('learn.fmt_' + c.format) }}</span>
          <span v-if="c.min_age" class="rounded-full bg-gold-200 px-2.5 py-0.5 text-ink">{{ t('learn.ages', { a: c.min_age }) }}</span>
        </div>
        <button class="mt-4 rounded-full bg-emerald-900 py-2 text-sm text-cream" @click="start(c)">{{ t('learn.interested') }}</button>
      </li>
    </ul>
    <p class="mt-6 text-xs text-ink-soft">{{ t('learn.phase') }}</p>

    <!-- Interest form -->
    <div v-if="open" class="fixed inset-0 z-50 grid place-items-end bg-ink/40 sm:place-items-center" @click.self="open = null">
      <div role="dialog" aria-modal="true" class="max-h-[92vh] w-full overflow-y-auto rounded-t-[var(--radius-sheet)] bg-cream p-6 sm:max-w-lg sm:rounded-[var(--radius-sheet)]">
        <div class="flex items-start gap-3">
          <h2 class="flex-1 font-display text-2xl text-emerald-900">{{ tr(open.title) }}</h2>
          <button class="grid size-8 place-items-center rounded-full border border-line" @click="open = null" :aria-label="t('quran.close')"><X class="size-4" /></button>
        </div>

        <div v-if="sent" class="mt-6 text-center">
          <CheckCircle2 class="mx-auto size-10 text-emerald-700" />
          <p class="mt-3 font-medium text-ink">{{ t('learn.thanks') }}</p>
          <p class="mt-1 text-sm text-ink-soft">{{ t('learn.thanksText') }}</p>
        </div>

        <form v-else class="mt-4 space-y-3 text-sm" @submit.prevent="submit">
          <input v-model="form.website" type="text" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />
          <label class="block"><span class="text-xs text-ink-soft">{{ t('learn.name') }}</span>
            <input v-model="form.name" required class="mt-1 w-full rounded-2xl border-line" /><span v-if="form.errors.name" class="text-xs text-red-700">{{ form.errors.name }}</span></label>
          <label class="block"><span class="text-xs text-ink-soft">{{ t('learn.email') }}</span>
            <input v-model="form.email" type="email" required class="mt-1 w-full rounded-2xl border-line" /><span v-if="form.errors.email" class="text-xs text-red-700">{{ form.errors.email }}</span></label>
          <label class="block"><span class="text-xs text-ink-soft">{{ t('learn.phone') }}</span>
            <input v-model="form.phone" type="tel" class="mt-1 w-full rounded-2xl border-line" /></label>
          <label class="block"><span class="text-xs text-ink-soft">{{ t('learn.format') }}</span>
            <select v-model="form.format" class="mt-1 w-full rounded-2xl border-line">
              <option value="either">{{ t('learn.either') }}</option><option value="one_to_one">{{ t('learn.oneToOne') }}</option><option value="group">{{ t('learn.group') }}</option>
            </select></label>
          <label class="flex items-center gap-2"><input v-model="form.for_child" type="checkbox" class="rounded text-emerald-900 focus:ring-gold-500" /> {{ t('learn.forChild') }}</label>
          <label v-if="form.for_child" class="block"><span class="text-xs text-ink-soft">{{ t('learn.childAge') }}</span>
            <select v-model="form.child_age_range" required class="mt-1 w-full rounded-2xl border-line">
              <option value="" disabled>—</option><option v-for="a in ['4-6', '7-9', '10-12', '13-17']" :key="a" :value="a">{{ a }}</option>
            </select>
            <span class="mt-1 block text-xs text-ink-soft">{{ t('learn.childNote') }}</span></label>
          <label class="block"><span class="text-xs text-ink-soft">{{ t('learn.message') }}</span>
            <textarea v-model="form.message" rows="3" maxlength="1000" class="mt-1 w-full rounded-2xl border-line" /></label>
          <label class="flex items-start gap-2"><input v-model="form.adult_confirm" type="checkbox" required class="mt-0.5 rounded text-emerald-900 focus:ring-gold-500" /> {{ t('learn.adult') }}</label>
          <label class="flex items-start gap-2"><input v-model="form.contact_consent" type="checkbox" required class="mt-0.5 rounded text-emerald-900 focus:ring-gold-500" /> {{ t('learn.consent') }}</label>
          <p v-if="form.errors.adult_confirm || form.errors.contact_consent" class="text-xs text-red-700">{{ t('learn.mustConfirm') }}</p>
          <button class="w-full rounded-full bg-emerald-900 py-2.5 text-cream disabled:opacity-50" :disabled="form.processing">{{ t('learn.send') }}</button>
        </form>
      </div>
    </div>
  </QurbaShell>
</template>
