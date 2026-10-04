// ===== QURBA useLocale — START =====
import { watchEffect } from 'vue';
import { useI18n } from 'vue-i18n';
import { RTL_LOCALES, type QurbaLocale } from '../lib/i18n';

export function useLocale() {
  const { locale } = useI18n();

  watchEffect(() => {
    const l = locale.value as QurbaLocale;
    document.documentElement.lang = l;
    document.documentElement.dir = RTL_LOCALES.includes(l) ? 'rtl' : 'ltr';
    try { localStorage.setItem('qurba.locale', l); } catch {}
  });

  const setLocale = (l: QurbaLocale) => { locale.value = l; };
  return { locale, setLocale };
}
// ===== QURBA useLocale — END =====
