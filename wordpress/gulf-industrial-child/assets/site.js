(() => {
  'use strict';
  const toggle = document.querySelector('.gulf-menu-toggle');
  const nav = document.querySelector('.gulf-nav');
  const close = () => { if (toggle && nav) { toggle.setAttribute('aria-expanded', 'false'); nav.classList.remove('is-open'); } };
  toggle?.addEventListener('click', () => {
    const open = toggle.getAttribute('aria-expanded') !== 'true';
    toggle.setAttribute('aria-expanded', String(open));
    nav.classList.toggle('is-open', open);
  });
  nav?.querySelectorAll('a').forEach(a => a.addEventListener('click', close));
  document.querySelectorAll('.gulf-dropdown-toggle').forEach(btn => btn.addEventListener('click', () => {
    const open = btn.getAttribute('aria-expanded') !== 'true';
    btn.setAttribute('aria-expanded', String(open));
    btn.parentElement.classList.toggle('is-open', open);
  }));
  document.addEventListener('keydown', e => { if (e.key !== 'Escape') return; document.querySelectorAll('.gulf-dropdown.is-open').forEach(d => { d.classList.remove('is-open'); d.querySelector('button').setAttribute('aria-expanded', 'false'); }); if (toggle?.getAttribute('aria-expanded') === 'true') { close(); toggle.focus(); } });
  document.querySelectorAll('[data-language]').forEach(a => a.addEventListener('click', () => {
    try { localStorage.setItem('gulf-language', a.dataset.language); } catch (_) { /* Storage may be unavailable in private browsing. */ }
  }));
  const config = window.gulfConfig;
  if (!config || config.explicit || config.editor) return;
  const changeLanguage = lang => {
    if (!['en', 'ar'].includes(lang) || lang === config.lang) return;
    const url = new URL(location.href);
    url.searchParams.set('lang', lang);
    location.replace(url.href);
  };
  let preference;
  try { preference = localStorage.getItem('gulf-language'); } catch (_) {}
  if (['en', 'ar'].includes(preference)) { changeLanguage(preference); return; }
  if (!config.endpoint) return;
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), 2500);
  fetch(config.endpoint, { cache: 'no-store', credentials: 'same-origin', signal: controller.signal })
    .then(r => { if (!r.ok) throw new Error('Language lookup failed'); return r.json(); })
    .then(data => changeLanguage(data.lang)).catch(() => {}).finally(() => clearTimeout(timer));
})();
