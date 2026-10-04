// ===== QURBA: ayah-by-ayah Quran audio player =====
import { reactive, watch } from 'vue';

export interface Reciter { id: number; slug: string; name: string; pattern: string; approved: boolean }
export type RepeatMode = 'off' | 'ayah' | 'surah';

const pad = (n: number) => String(n).padStart(3, '0');
export const ayahUrl = (pattern: string, s: number, a: number) =>
  pattern.replace('{sss}', pad(s)).replace('{aaa}', pad(a)).replace('{s}', String(s)).replace('{a}', String(a));

function load<T>(k: string, f: T): T { try { const v = localStorage.getItem(k); return v ? { ...f, ...JSON.parse(v) } : f; } catch { return f; } }

export const audioPrefs = reactive(load('qurba.audio', { reciterId: 0, speed: 1, repeat: 'off' as RepeatMode }));
watch(audioPrefs, (v) => { try { localStorage.setItem('qurba.audio', JSON.stringify(v)); } catch {} }, { deep: true });

export const player = reactive({
  playing: false, loading: false, surah: 0, ayah: 0, total: 0, error: '',
  inBismillah: false, sleepAt: 0 as number, progress: 0,
});

let el: HTMLAudioElement | null = null;
let preload: HTMLAudioElement | null = null;
let reciter: Reciter | null = null;
let withBismillah = false;
let sleepTimer: number | undefined;

function audio(): HTMLAudioElement {
  if (el) return el;
  el = new Audio();
  el.preload = 'auto';
  el.addEventListener('playing', () => { player.playing = true; player.loading = false; });
  el.addEventListener('waiting', () => { player.loading = true; });
  el.addEventListener('pause', () => { player.playing = false; });
  el.addEventListener('timeupdate', () => { player.progress = el!.duration ? el!.currentTime / el!.duration : 0; });
  el.addEventListener('error', () => { player.loading = false; player.playing = false; player.error = 'load'; });
  el.addEventListener('ended', onEnded);
  return el;
}

function src(s: number, a: number) { return reciter ? ayahUrl(reciter.pattern, s, a) : ''; }

function load_(s: number, a: number, bismillah = false) {
  const e = audio();
  player.error = '';
  player.loading = true;
  player.inBismillah = bismillah;
  e.src = bismillah ? src(1, 1) : src(s, a);
  e.playbackRate = audioPrefs.speed;
  e.play().catch(() => { player.loading = false; });
  if (!bismillah) {
    player.ayah = a;
    const nextA = a < player.total ? a + 1 : 0;
    if (nextA) { preload = new Audio(src(s, nextA)); preload.preload = 'auto'; }
  }
  mediaSession();
}

function onEnded() {
  if (player.inBismillah) return load_(player.surah, player.ayah);
  if (audioPrefs.repeat === 'ayah') return load_(player.surah, player.ayah);
  if (player.ayah < player.total) return load_(player.surah, player.ayah + 1);
  if (audioPrefs.repeat === 'surah') return start(player.surah, 1, player.total);
  player.playing = false;
}

export function setReciter(r: Reciter | null) { reciter = r; }

/** Start a surah at an ayah. Surahs other than 1 and 9 get the Bismillah before ayah 1. */
export function start(surah: number, ayah: number, total: number) {
  player.surah = surah; player.total = total;
  withBismillah = ayah === 1 && surah !== 1 && surah !== 9;
  player.ayah = ayah;
  load_(surah, ayah, withBismillah);
}

export function toggle() {
  const e = audio();
  if (!e.src) return;
  e.paused ? e.play() : e.pause();
}
export function stop() { el?.pause(); player.playing = false; player.surah = 0; player.ayah = 0; }
export function next() { if (player.ayah < player.total) load_(player.surah, player.ayah + 1); }
export function prev() { if (player.ayah > 1) load_(player.surah, player.ayah - 1); }
export function setSpeed(v: number) { audioPrefs.speed = v; if (el) el.playbackRate = v; }

export function setSleep(minutes: number) {
  clearTimeout(sleepTimer);
  player.sleepAt = minutes ? Date.now() + minutes * 60000 : 0;
  if (minutes) sleepTimer = window.setTimeout(() => { el?.pause(); player.sleepAt = 0; }, minutes * 60000);
}

function mediaSession() {
  if (!('mediaSession' in navigator) || !reciter) return;
  navigator.mediaSession.metadata = new MediaMetadata({
    title: `${player.surah}:${player.ayah}`, artist: reciter.name, album: 'Qurba',
    artwork: [{ src: '/brand/qurba-icon-512.png', sizes: '512x512', type: 'image/png' }],
  });
  navigator.mediaSession.setActionHandler('play', () => el?.play());
  navigator.mediaSession.setActionHandler('pause', () => el?.pause());
  navigator.mediaSession.setActionHandler('nexttrack', next);
  navigator.mediaSession.setActionHandler('previoustrack', prev);
}
