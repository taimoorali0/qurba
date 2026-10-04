// ===== QURBA i18n — START =====
// In resources/js/app.ts add:
//   import { i18n } from './lib/i18n';
//   ...createApp({ render: () => h(App, props) }).use(plugin).use(i18n).mount(el);
import { createI18n } from 'vue-i18n';
import en from '../locales/en.json';
import ar from '../locales/ar.json';
import ur from '../locales/ur.json';

export type QurbaLocale = 'en' | 'ar' | 'ur';
export const RTL_LOCALES: QurbaLocale[] = ['ar', 'ur'];

let saved: QurbaLocale | null = null;
try { saved = localStorage.getItem('qurba.locale') as QurbaLocale | null; } catch {}

export const i18n = createI18n({
  legacy: false,
  locale: saved ?? 'en',
  fallbackLocale: 'en',
  messages: { en, ar, ur },
});
// ===== QURBA i18n — END =====
