// ===== QURBA: Zakat calculation (2.5% of zakatable wealth held for a lunar year, above nisab) =====
import { reactive, watch } from 'vue';

/** Nisab weights in grams: 7.5 tola gold and 52.5 tola silver (1 tola = 11.664 g). */
export const NISAB_GOLD_G = 87.48;
export const NISAB_SILVER_G = 612.36;
export const RATE = 0.025;

export interface ZakatInput {
  currency: string; basis: 'silver' | 'gold'; goldPrice: number; silverPrice: number;
  cash: number; bank: number; goldGrams: number; silverGrams: number; investments: number;
  business: number; receivables: number; debts: number;
}
const KEY = 'qurba.zakat';
const empty: ZakatInput = { currency: '', basis: 'silver', goldPrice: 0, silverPrice: 0, cash: 0, bank: 0, goldGrams: 0, silverGrams: 0, investments: 0, business: 0, receivables: 0, debts: 0 };
function load(): ZakatInput { try { return { ...empty, ...JSON.parse(localStorage.getItem(KEY) || '{}') }; } catch { return { ...empty }; } }

export const zakat = reactive<ZakatInput>(typeof window === 'undefined' ? { ...empty } : load());
watch(zakat, (v) => { try { localStorage.setItem(KEY, JSON.stringify(v)); } catch { /* storage unavailable */ } }, { deep: true });
export function resetZakat() { Object.assign(zakat, { ...empty, currency: zakat.currency, goldPrice: zakat.goldPrice, silverPrice: zakat.silverPrice }); }

const n = (v: unknown) => (Number.isFinite(Number(v)) && Number(v) > 0 ? Number(v) : 0);

export function calculate(z: ZakatInput) {
  const gold = n(z.goldGrams) * n(z.goldPrice);
  const silver = n(z.silverGrams) * n(z.silverPrice);
  const assets = n(z.cash) + n(z.bank) + gold + silver + n(z.investments) + n(z.business) + n(z.receivables);
  const net = Math.max(0, assets - n(z.debts));
  const nisab = z.basis === 'gold' ? NISAB_GOLD_G * n(z.goldPrice) : NISAB_SILVER_G * n(z.silverPrice);
  const due = nisab > 0 && net >= nisab;
  return { gold, silver, assets, net, nisab, due, amount: due ? net * RATE : 0 };
}
