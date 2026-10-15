<!-- ===== QURBA: Zakat calculator (values stay on the device) ===== -->
<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { RotateCcw } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import PageTitle from '../components/PageTitle.vue';
import QurbaShell from '../layouts/QurbaShell.vue';
import { calculate, NISAB_GOLD_G, NISAB_SILVER_G, resetZakat, zakat, type ZakatInput } from '../lib/zakat';

const { t, locale } = useI18n();
const r = computed(() => calculate(zakat));
const money = (v: number) => `${v.toLocaleString(locale.value, { maximumFractionDigits: 2 })}${zakat.currency ? ' ' + zakat.currency : ''}`;

type Field = { key: keyof ZakatInput; unit?: 'g' };
const sections: { title: string; fields: Field[] }[] = [
  { title: 'zakat.s_cash', fields: [{ key: 'cash' }, { key: 'bank' }] },
  { title: 'zakat.s_metals', fields: [{ key: 'goldGrams', unit: 'g' }, { key: 'silverGrams', unit: 'g' }] },
  { title: 'zakat.s_other', fields: [{ key: 'investments' }, { key: 'business' }, { key: 'receivables' }] },
  { title: 'zakat.s_debts', fields: [{ key: 'debts' }] },
];
</script>

<template>
  <Head :title="t('zakat.title')" />
  <QurbaShell>
    <PageTitle :title="t('zakat.title')" arabic="الزَّكَاة" :sub="t('zakat.sub')" />

    <div class="mt-6 grid gap-6 lg:grid-cols-[1.5fr_1fr]">
      <div class="space-y-4">
        <!-- Prices and nisab basis -->
        <section class="rounded-[var(--radius-tile)] bg-paper p-5 shadow-soft">
          <h2 class="font-display text-lg text-emerald-900">{{ t('zakat.s_prices') }}</h2>
          <div class="mt-3 grid gap-3 sm:grid-cols-3">
            <label class="text-sm text-ink-soft">{{ t('zakat.currency') }}
              <input v-model.trim="zakat.currency" type="text" maxlength="5" placeholder="PKR" class="mt-1 w-full rounded-xl border-line text-ink focus:border-gold-500 focus:ring-0" />
            </label>
            <label class="text-sm text-ink-soft">{{ t('zakat.goldPrice') }}
              <input v-model.number="zakat.goldPrice" type="number" min="0" step="any" inputmode="decimal" class="mt-1 w-full rounded-xl border-line text-ink focus:border-gold-500 focus:ring-0" />
            </label>
            <label class="text-sm text-ink-soft">{{ t('zakat.silverPrice') }}
              <input v-model.number="zakat.silverPrice" type="number" min="0" step="any" inputmode="decimal" class="mt-1 w-full rounded-xl border-line text-ink focus:border-gold-500 focus:ring-0" />
            </label>
          </div>
          <fieldset class="mt-4">
            <legend class="text-sm text-ink-soft">{{ t('zakat.basis') }}</legend>
            <div class="mt-2 flex flex-wrap gap-2">
              <label v-for="b in (['silver', 'gold'] as const)" :key="b" class="cursor-pointer rounded-full border px-4 py-2 text-sm"
                :class="zakat.basis === b ? 'border-emerald-900 bg-emerald-900 text-cream' : 'border-line text-ink hover:border-gold-500'">
                <input v-model="zakat.basis" type="radio" :value="b" class="sr-only" />
                {{ t('zakat.basis_' + b, { g: b === 'gold' ? NISAB_GOLD_G : NISAB_SILVER_G }) }}
              </label>
            </div>
          </fieldset>
        </section>

        <section v-for="s in sections" :key="s.title" class="rounded-[var(--radius-tile)] bg-paper p-5 shadow-soft">
          <h2 class="font-display text-lg text-emerald-900">{{ t(s.title) }}</h2>
          <div class="mt-3 grid gap-3 sm:grid-cols-2">
            <label v-for="f in s.fields" :key="f.key" class="text-sm text-ink-soft">{{ t('zakat.f_' + f.key) }}
              <span class="relative mt-1 block">
                <input v-model.number="zakat[f.key]" type="number" min="0" step="any" inputmode="decimal" placeholder="0"
                  class="w-full rounded-xl border-line text-ink focus:border-gold-500 focus:ring-0" :class="f.unit ? 'pe-10' : ''" />
                <span v-if="f.unit" class="pointer-events-none absolute end-3 top-1/2 -translate-y-1/2 text-xs">{{ t('zakat.grams') }}</span>
              </span>
            </label>
          </div>
        </section>
      </div>

      <!-- Result -->
      <aside class="lg:sticky lg:top-24 lg:self-start">
        <section class="rounded-[var(--radius-tile)] bg-gradient-to-br from-teal-950 via-teal-900 to-teal-700 p-6 text-cream shadow-lift" aria-live="polite">
          <p class="text-sm text-gold-200">{{ t('zakat.due') }}</p>
          <p class="mt-1 font-display text-4xl text-gold-500">{{ money(r.amount) }}</p>
          <p class="mt-2 text-sm text-cream/90">
            <template v-if="!r.nisab">{{ t('zakat.needPrice') }}</template>
            <template v-else-if="r.due">{{ t('zakat.above') }}</template>
            <template v-else>{{ t('zakat.below') }}</template>
          </p>
          <dl class="mt-5 space-y-2 border-t border-white/15 pt-4 text-sm">
            <div class="flex justify-between gap-3"><dt class="text-gold-200">{{ t('zakat.assets') }}</dt><dd class="tabular-nums">{{ money(r.assets) }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-gold-200">{{ t('zakat.f_debts') }}</dt><dd class="tabular-nums">− {{ money(Number(zakat.debts) || 0) }}</dd></div>
            <div class="flex justify-between gap-3 font-medium"><dt class="text-gold-200">{{ t('zakat.net') }}</dt><dd class="tabular-nums">{{ money(r.net) }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-gold-200">{{ t('zakat.nisab') }}</dt><dd class="tabular-nums">{{ money(r.nisab) }}</dd></div>
          </dl>
          <button type="button" class="mt-5 inline-flex items-center gap-1.5 rounded-full border border-gold-200/40 px-3 py-1.5 text-xs text-gold-200 hover:bg-white/10" @click="resetZakat">
            <RotateCcw class="size-3.5" /> {{ t('zakat.reset') }}
          </button>
        </section>
        <p class="mt-3 px-1 text-xs leading-relaxed text-ink-soft">{{ t('zakat.note') }}</p>
      </aside>
    </div>
  </QurbaShell>
</template>
