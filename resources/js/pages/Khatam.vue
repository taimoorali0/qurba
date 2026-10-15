<!-- ===== QURBA: Khatam planner — finish the Quran in a chosen number of days ===== -->
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Check, RotateCcw } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import PageTitle from '../components/PageTitle.vue';
import QurbaShell from '../layouts/QurbaShell.vue';
import { JUZ_START, PLAN_LENGTHS, khatam, planStatus, resetPlan, startPlan, toggleJuz } from '../lib/khatam';

const props = defineProps<{ surahs: { id: number; name_simple: string }[] }>();
const { t } = useI18n();
const choice = ref(30);
const status = computed(() => planStatus());
const surahName = (id: number) => props.surahs.find((s) => s.id === id)?.name_simple ?? `Surah ${id}`;
const pct = computed(() => Math.round(((status.value?.done ?? 0) / 30) * 100));
function confirmReset() {
    if (confirm(t('khatam.reset') + '?')) resetPlan();
}
</script>

<template>
    <Head :title="t('khatam.title')" />
    <QurbaShell>
        <PageTitle :title="t('khatam.title')" arabic="خَتْمُ الْقُرْآن" :sub="t('khatam.sub')" />

        <!-- No plan yet: choose a length -->
        <section v-if="!khatam.plan" class="mt-6 rounded-[var(--radius-tile)] bg-paper p-6 shadow-soft">
            <p class="text-ink-soft">{{ t('khatam.none') }}</p>
            <div class="mt-4 flex flex-wrap gap-2" role="radiogroup">
                <button
                    v-for="n in PLAN_LENGTHS"
                    :key="n"
                    type="button"
                    role="radio"
                    :aria-checked="choice === n"
                    class="rounded-full border px-5 py-2 text-sm transition-colors"
                    :class="choice === n ? 'border-emerald-900 bg-emerald-900 text-cream' : 'border-line bg-paper text-ink hover:border-gold-500'"
                    @click="choice = n"
                >
                    {{ t('khatam.days', { n }) }}
                </button>
            </div>
            <button type="button" class="mt-6 rounded-full bg-gold-500 px-6 py-2.5 text-sm font-medium text-emerald-950" @click="startPlan(choice)">
                {{ t('khatam.start') }}
            </button>
        </section>

        <template v-else-if="status">
            <!-- Progress -->
            <section class="mt-6 rounded-[var(--radius-tile)] bg-gradient-to-br from-teal-950 via-teal-900 to-teal-700 p-6 text-cream shadow-lift">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-sm text-gold-200">{{ t('khatam.dayOf', { d: status.day, n: status.days }) }}</p>
                        <p class="mt-1 font-display text-2xl text-white md:text-3xl">{{ t('khatam.progress', { done: status.done }) }}</p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-full border border-gold-200/40 px-3 py-1.5 text-xs text-gold-200 hover:bg-white/10"
                        @click="confirmReset"
                    >
                        <RotateCcw class="size-3.5" /> {{ t('khatam.reset') }}
                    </button>
                </div>
                <div
                    class="mt-4 h-2.5 overflow-hidden rounded-full bg-white/15"
                    role="progressbar"
                    :aria-valuenow="pct"
                    aria-valuemin="0"
                    aria-valuemax="100"
                >
                    <div class="h-full rounded-full bg-gold-500 transition-all" :style="{ width: pct + '%' }" />
                </div>
                <p class="mt-3 text-sm text-gold-200">
                    <template v-if="status.finished">{{ t('khatam.finished') }}</template>
                    <template v-else
                        >{{ t('khatam.todayTarget', { n: status.target }) }} ·
                        {{ status.behind ? t('khatam.behind', { n: status.behind }) : t('khatam.ahead') }}</template
                    >
                </p>
            </section>

            <!-- Juz checklist -->
            <ul class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                <li v-for="(start, i) in JUZ_START" :key="i">
                    <div
                        class="flex h-full flex-col rounded-[var(--radius-tile)] border bg-paper p-4 shadow-soft"
                        :class="
                            khatam.plan.done.includes(i + 1)
                                ? 'border-emerald-500'
                                : i + 1 <= status.target
                                  ? 'border-gold-500'
                                  : 'border-transparent'
                        "
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-medium text-emerald-900">{{ t('khatam.juz', { n: i + 1 }) }}</span>
                            <button
                                type="button"
                                class="grid size-8 place-items-center rounded-full border transition-colors"
                                :class="
                                    khatam.plan.done.includes(i + 1)
                                        ? 'border-emerald-700 bg-emerald-700 text-cream'
                                        : 'border-line text-transparent hover:border-emerald-700'
                                "
                                :aria-pressed="khatam.plan.done.includes(i + 1)"
                                :aria-label="t('khatam.juz', { n: i + 1 })"
                                @click="toggleJuz(i + 1)"
                            >
                                <Check class="size-4" />
                            </button>
                        </div>
                        <p class="mt-1 flex-1 text-xs text-ink-soft">{{ t('khatam.startsAt', { surah: surahName(start[0]), ayah: start[1] }) }}</p>
                        <Link
                            :href="`/quran/${start[0]}#ayah-${start[1]}`"
                            class="mt-3 text-sm text-emerald-900 underline-offset-4 hover:underline"
                            >{{ t('khatam.open') }}</Link
                        >
                    </div>
                </li>
            </ul>
        </template>
    </QurbaShell>
</template>
