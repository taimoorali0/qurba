// ===== QURBA: privacy & consent choices (separate, never one giant checkbox) =====
import { reactive, watch } from 'vue';

export const CONSENT_VERSION = '1';
export const CONSENT_TYPES = ['cloud_backup', 'notifications', 'location', 'analytics', 'crash_reports', 'personalization', 'mobile_data_audio'] as const;
export type ConsentType = typeof CONSENT_TYPES[number];

interface Consent { items: Record<ConsentType, boolean>; t: number; decided: boolean }
const defaults = (): Consent => ({
  items: { cloud_backup: false, notifications: false, location: false, analytics: false, crash_reports: false, personalization: true, mobile_data_audio: false },
  t: 0, decided: false,
});
function load(): Consent { try { const v = localStorage.getItem('qurba.consent'); return v ? { ...defaults(), ...JSON.parse(v) } : defaults(); } catch { return defaults(); } }
export const consent = reactive<Consent>(load());
watch(consent, (v) => { try { localStorage.setItem('qurba.consent', JSON.stringify(v)); } catch {} }, { deep: true });

export function setConsent(type: ConsentType, on: boolean) { consent.items[type] = on; consent.t = Date.now(); consent.decided = true; }
