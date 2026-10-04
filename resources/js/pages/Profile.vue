<!-- ===== QURBA: Profile, sync, privacy & consent, devices ===== -->
<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { CloudUpload, Download, LogIn, LogOut, RefreshCw, Smartphone, Trash2, UserPlus, Settings } from 'lucide-vue-next';
import QurbaShell from '../layouts/QurbaShell.vue';
import { CONSENT_TYPES, consent, setConsent, type ConsentType } from '../lib/consent';
import { app, syncNow } from '../lib/pwa';
import { lastDevices } from '../lib/userSync';
import { useLocale } from '../composables/useLocale';
import { CITIES, loc, setCity } from '../lib/location';

const { t, locale } = useI18n();
const { setLocale } = useLocale();
const page = usePage<any>();
const user = computed(() => page.props.auth?.user ?? null);
const devices = ref<any[]>([]);
const msg = ref('');

async function doSync() {
  msg.value = '';
  const ok = await syncNow();
  devices.value = lastDevices;
  msg.value = ok ? t('profile.synced') : t('profile.syncFailed');
}
onMounted(() => { if (user.value && consent.items.cloud_backup && app.online) doSync(); });

function toggle(type: ConsentType, e: Event) {
  setConsent(type, (e.target as HTMLInputElement).checked);
  if (type === 'cloud_backup' && consent.items.cloud_backup && user.value) doSync();
}

function xsrf() { const m = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/); return m ? decodeURIComponent(m[1]) : ''; }
async function removeDevice(id: number) {
  await fetch(`/api/v1/devices/${id}`, { method: 'DELETE', credentials: 'same-origin', headers: { 'X-XSRF-TOKEN': xsrf(), Accept: 'application/json' } });
  devices.value = devices.value.filter((d) => d.id !== id);
}
const myDevice = (() => { try { return localStorage.getItem('qurba.device'); } catch { return null; } })();
const deviceName = (ua: string) => /iphone|ipad/i.test(ua) ? 'iPhone / iPad' : /android/i.test(ua) ? 'Android' : /windows/i.test(ua) ? 'Windows' : /mac os/i.test(ua) ? 'Mac' : 'Browser';
const lastSyncText = computed(() => app.lastSync ? new Date(app.lastSync).toLocaleString(locale.value, { dateStyle: 'medium', timeStyle: 'short' }) : t('profile.never'));
function pickCountry(e: Event) { const c = CITIES.find((x) => x.country === (e.target as HTMLSelectElement).value); if (c) setCity(c); }
const countries = [...new Map(CITIES.map((c) => [c.country, c])).values()];
const countryName = (code: string) => new Intl.DisplayNames([locale.value], { type: 'region' }).of(code) ?? code;
</script>

<template>
  <Head :title="t('nav.profile')" />
  <QurbaShell>
    <h1 class="font-display text-3xl text-emerald-900 md:text-4xl">{{ t('nav.profile') }}</h1>

    <div class="mt-6 grid gap-5 lg:grid-cols-2">
      <!-- Account -->
      <section class="rounded-[var(--radius-sheet)] border border-line bg-paper p-6">
        <h2 class="font-display text-xl text-emerald-900">{{ t('profile.account') }}</h2>
        <template v-if="user">
          <p class="mt-3 font-medium text-ink">{{ user.name }}</p>
          <p class="text-sm text-ink-soft">{{ user.email }}</p>
          <div class="mt-4 flex flex-wrap gap-2 text-sm">
            <Link href="/settings/profile" class="inline-flex items-center gap-2 rounded-full border border-line px-4 py-1.5"><Settings class="size-4" /> {{ t('profile.accountSettings') }}</Link>
            <Link href="/logout" method="post" as="button" class="inline-flex items-center gap-2 rounded-full border border-line px-4 py-1.5"><LogOut class="size-4" /> {{ t('profile.logout') }}</Link>
          </div>
        </template>
        <template v-else>
          <p class="mt-3 text-sm text-ink-soft">{{ t('profile.guest') }}</p>
          <div class="mt-4 flex flex-wrap gap-2 text-sm">
            <Link href="/register" class="inline-flex items-center gap-2 rounded-full bg-emerald-900 px-4 py-1.5 text-cream"><UserPlus class="size-4" /> {{ t('profile.register') }}</Link>
            <Link href="/login" class="inline-flex items-center gap-2 rounded-full border border-line px-4 py-1.5"><LogIn class="size-4" /> {{ t('profile.login') }}</Link>
          </div>
        </template>
      </section>

      <!-- Sync -->
      <section class="rounded-[var(--radius-sheet)] border border-line bg-paper p-6">
        <h2 class="font-display text-xl text-emerald-900">{{ t('profile.backup') }}</h2>
        <p class="mt-3 text-sm text-ink-soft">{{ t('profile.lastSync') }}: <span class="text-ink">{{ lastSyncText }}</span></p>
        <p v-if="!user" class="mt-2 text-sm text-ink-soft">{{ t('profile.syncNeedsAccount') }}</p>
        <p v-else-if="!consent.items.cloud_backup" class="mt-2 text-sm text-ink-soft">{{ t('profile.syncNeedsConsent') }}</p>
        <button class="mt-4 inline-flex items-center gap-2 rounded-full bg-emerald-900 px-4 py-1.5 text-sm text-cream disabled:opacity-50" :disabled="app.syncing || !app.online" @click="doSync">
          <RefreshCw class="size-4" :class="app.syncing ? 'animate-spin' : ''" /> {{ t('pwa.syncNow') }}
        </button>
        <p v-if="msg" class="mt-2 text-xs text-ink-soft">{{ msg }}</p>
      </section>

      <!-- Language & country -->
      <section class="rounded-[var(--radius-sheet)] border border-line bg-paper p-6 text-sm">
        <h2 class="font-display text-xl text-emerald-900">{{ t('profile.regional') }}</h2>
        <label class="mt-4 block">
          <span class="text-xs text-ink-soft">{{ t('lang') }}</span>
          <select :value="locale" @change="setLocale(($event.target as HTMLSelectElement).value as any)" class="mt-1 w-full rounded-full border-line py-1.5 text-sm">
            <option value="en">English</option><option value="ar">العربية</option><option value="ur">اردو</option>
          </select>
        </label>
        <label class="mt-3 block">
          <span class="text-xs text-ink-soft">{{ t('profile.country') }}</span>
          <select :value="loc.place?.country ?? ''" @change="pickCountry" class="mt-1 w-full rounded-full border-line py-1.5 text-sm">
            <option value="">—</option>
            <option v-for="c in countries" :key="c.country" :value="c.country">{{ countryName(c.country) }}</option>
          </select>
          <span class="mt-1 block text-xs text-ink-soft">{{ t('profile.countryHint') }}</span>
        </label>
      </section>

      <!-- Privacy & consent -->
      <section class="rounded-[var(--radius-sheet)] border border-line bg-paper p-6 text-sm">
        <h2 class="font-display text-xl text-emerald-900">{{ t('profile.privacy') }}</h2>
        <ul class="mt-3 divide-y divide-line">
          <li v-for="c in CONSENT_TYPES" :key="c" class="flex items-start justify-between gap-4 py-3">
            <span><span class="block text-ink">{{ t('consent.' + c) }}</span><span class="text-xs text-ink-soft">{{ t('consent.' + c + '_d') }}</span></span>
            <input type="checkbox" :checked="consent.items[c]" @change="toggle(c, $event)" class="mt-1 rounded text-emerald-900 focus:ring-gold-500" />
          </li>
        </ul>
        <div v-if="user" class="mt-4 flex flex-wrap gap-2">
          <a href="/api/v1/me/export" class="inline-flex items-center gap-2 rounded-full border border-line px-4 py-1.5"><Download class="size-4" /> {{ t('profile.export') }}</a>
          <Link href="/settings/profile" class="inline-flex items-center gap-2 rounded-full border border-red-200 px-4 py-1.5 text-red-700"><Trash2 class="size-4" /> {{ t('profile.delete') }}</Link>
        </div>
      </section>

      <!-- Devices -->
      <section v-if="user" class="rounded-[var(--radius-sheet)] border border-line bg-paper p-6 text-sm lg:col-span-2">
        <h2 class="font-display text-xl text-emerald-900">{{ t('profile.devices') }}</h2>
        <p v-if="!devices.length" class="mt-3 text-ink-soft">{{ t('profile.noDevices') }}</p>
        <ul class="mt-3 divide-y divide-line">
          <li v-for="d in devices" :key="d.id" class="flex items-center gap-3 py-3">
            <Smartphone class="size-4 text-emerald-700" />
            <span class="flex-1">{{ deviceName(d.name || '') }} · {{ d.platform }} <span v-if="d.device_uuid === myDevice" class="ms-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] text-emerald-900">{{ t('profile.thisDevice') }}</span>
              <span class="block text-xs text-ink-soft">{{ d.last_synced_at }}</span></span>
            <button v-if="d.device_uuid !== myDevice" class="grid size-8 place-items-center rounded-full text-ink-soft hover:text-red-700" @click="removeDevice(d.id)" :aria-label="t('tasbeeh.remove')"><Trash2 class="size-4" /></button>
          </li>
        </ul>
      </section>
    </div>
  </QurbaShell>
</template>
