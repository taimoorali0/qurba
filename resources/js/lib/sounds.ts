// ===== QURBA: app sounds — soft background audio, adhan at prayer time, 99 Names recitations =====
// Audio files are not bundled: add licensed recordings under public/audio/ (see public/audio/README.md).
import { reactive, watch } from 'vue';
import { loc } from './location';
import { timesFor } from './prayer';
import { player as quranPlayer } from './quranAudio';
import { rem } from './reminders';

export const FILES = {
  ambient: '/audio/ambient.mp3',
  adhan: '/audio/adhan.mp3',
  adhanFajr: '/audio/adhan-fajr.mp3',
  namesFull: '/audio/names-full.mp3',
  name: (n: number) => `/audio/names/${n}.mp3`,
};

interface Prefs { ambientOn: boolean; volume: number; muted: boolean; adhanOn: boolean }
function load(): Prefs {
  const d: Prefs = { ambientOn: true, volume: 0.25, muted: false, adhanOn: true };
  try { const v = localStorage.getItem('qurba.sounds'); return v ? { ...d, ...JSON.parse(v) } : d; } catch { return d; }
}
export const soundPrefs = reactive<Prefs>(load());
watch(soundPrefs, (v) => { try { localStorage.setItem('qurba.sounds', JSON.stringify(v)); } catch {} }, { deep: true });

export const sound = reactive({ ambientPlaying: false, ambientAvailable: null as boolean | null, adhanPlaying: '', namePlaying: 0, clipPlaying: '' });

// ---- File availability: one small manifest from the server instead of probing each file ----
export interface VoiceInfo { label: string; dir: string; names: number[]; full: string | null }
interface Manifest { ambient: boolean; adhan: boolean; adhanFajr: boolean; namesFull?: boolean; names: number[]; voices?: Record<string, VoiceInfo> }
let manifest: Promise<Manifest> | null = null;
function getManifest(): Promise<Manifest> {
  manifest ??= fetch('/api/v1/audio-manifest', { headers: { Accept: 'application/json' } })
    .then((r) => (r.ok ? r.json() : Promise.reject()))
    .catch(() => ({ ambient: false, adhan: false, adhanFajr: false, names: [], voices: {} }));
  return manifest;
}
/** Recorded voices for the 99 Names (standard, kids, …) */
export async function nameVoices(): Promise<Record<string, VoiceInfo>> {
  return (await getManifest()).voices ?? {};
}

export async function hasFile(url: string): Promise<boolean> {
  const m = await getManifest();
  if (url === FILES.ambient) return m.ambient;
  if (url === FILES.adhan) return m.adhan;
  if (url === FILES.adhanFajr) return m.adhanFajr;
  if (url === FILES.namesFull) return !!m.namesFull;
  const n = url.match(/\/audio\/names\/(\d+)\.mp3$/);
  return n ? m.names.includes(Number(n[1])) : false;
}

// ---- Background (ambient) audio: loops softly, starts on the first tap (browsers block autoplay) ----
let ambient: HTMLAudioElement | null = null;
let fade: number | undefined;
const targetVolume = () => (soundPrefs.muted ? 0 : soundPrefs.volume);

function fadeTo(v: number, done?: () => void) {
  clearInterval(fade);
  fade = window.setInterval(() => {
    if (!ambient) return clearInterval(fade);
    const step = 0.02;
    const cur = ambient.volume;
    if (Math.abs(cur - v) <= step) { ambient.volume = v; clearInterval(fade); done?.(); return; }
    ambient.volume = Math.max(0, Math.min(1, cur + (v > cur ? step : -step)));
  }, 60);
}

export async function playAmbient() {
  if (!(await hasFile(FILES.ambient))) { sound.ambientAvailable = false; return; }
  sound.ambientAvailable = true;
  if (!ambient) { ambient = new Audio(FILES.ambient); ambient.loop = true; ambient.volume = 0; }
  try { await ambient.play(); sound.ambientPlaying = true; fadeTo(targetVolume()); } catch { sound.ambientPlaying = false; }
}
export function pauseAmbient() {
  if (!ambient) return;
  fadeTo(0, () => { ambient?.pause(); });
  sound.ambientPlaying = false;
}
export function toggleAmbient() {
  if (sound.ambientPlaying) { soundPrefs.ambientOn = false; pauseAmbient(); } else { soundPrefs.ambientOn = true; playAmbient(); }
}
watch(() => [soundPrefs.volume, soundPrefs.muted], () => { if (ambient && sound.ambientPlaying) fadeTo(targetVolume()); });

// Quran recitation, adhan and name recitations take priority over the background sound
let resumeLater = false;
const busy = () => quranPlayer.playing || !!sound.adhanPlaying || !!sound.namePlaying || !!sound.clipPlaying;
watch(busy, (b) => {
  if (b && sound.ambientPlaying) { pauseAmbient(); resumeLater = true; }
  else if (!b && resumeLater && soundPrefs.ambientOn) { resumeLater = false; playAmbient(); }
});

// ---- One-shot audio (adhan, a name) ----
let oneShot: HTMLAudioElement | null = null;
function playOnce(url: string, onEnd: () => void): Promise<boolean> {
  oneShot?.pause();
  oneShot = new Audio(url);
  oneShot.volume = soundPrefs.muted ? 0 : 1;
  oneShot.addEventListener('ended', onEnd);
  oneShot.addEventListener('error', onEnd);
  return oneShot.play().then(() => true).catch(() => { onEnd(); return false; });
}
export function stopOneShot() {
  oneShot?.pause();
  if (typeof speechSynthesis !== 'undefined') speechSynthesis.cancel();
  sound.adhanPlaying = ''; sound.namePlaying = 0; sound.clipPlaying = '';
}

// ---- Device Arabic voice: used for a name until its recording has been uploaded ----
export const canSpeakArabic = () => typeof speechSynthesis !== 'undefined' && typeof SpeechSynthesisUtterance !== 'undefined';
function arabicVoice(): SpeechSynthesisVoice | undefined {
  return speechSynthesis.getVoices().find((v) => v.lang.toLowerCase().startsWith('ar'));
}
function speak(text: string, onEnd: () => void): boolean {
  if (!canSpeakArabic()) return false;
  speechSynthesis.cancel();
  const u = new SpeechSynthesisUtterance(text);
  u.lang = 'ar-SA';
  const v = arabicVoice(); if (v) u.voice = v;
  u.rate = 0.8;
  u.volume = soundPrefs.muted ? 0 : 1;
  u.onend = onEnd; u.onerror = onEnd;
  speechSynthesis.speak(u);
  return true;
}

/** Plays the adhan (a separate Fajr adhan is used when provided). Returns false if no file or the browser blocked it. */
export async function playAdhan(prayer: string): Promise<boolean> {
  const url = prayer === 'fajr' && (await hasFile(FILES.adhanFajr)) ? FILES.adhanFajr : FILES.adhan;
  if (!(await hasFile(url))) return false;
  sound.adhanPlaying = prayer;
  return playOnce(url, () => { sound.adhanPlaying = ''; });
}

/** Plays any recording (e.g. a dua); the key identifies what is playing. */
export function playClip(key: string, url: string): Promise<boolean> {
  sound.clipPlaying = key;
  return playOnce(url, () => { if (sound.clipPlaying === key) sound.clipPlaying = ''; });
}

/**
 * Plays one of the 99 Names in the chosen voice: its uploaded recording, otherwise
 * the device's Arabic voice reading `arabic`. voice = '' means device voice only.
 */
export async function playName(n: number, onEnd?: () => void, arabic?: string, voice = 'standard'): Promise<boolean> {
  const done = () => { if (sound.namePlaying === n) sound.namePlaying = 0; onEnd?.(); };
  const v = voice ? (await nameVoices())[voice] : undefined;
  if (v?.names.includes(n)) {
    sound.namePlaying = n;
    return playOnce(`/audio/${v.dir}/${n}.mp3`, done);
  }
  if (!arabic) return false;
  oneShot?.pause();
  sound.namePlaying = n;
  if (speak(arabic, done)) return true;
  sound.namePlaying = 0;
  return false;
}

/** Whether a real recording exists for this name (otherwise the device voice is used) */
export const hasNameRecording = (n: number) => hasFile(FILES.name(n));

// ---- Adhan while the app is open: checks every 20 seconds ----
const FARD = ['fajr', 'dhuhr', 'asr', 'maghrib', 'isha'];
function checkAdhan() {
  if (!soundPrefs.adhanOn || !loc.place) return;
  const now = Date.now();
  for (const p of timesFor(loc.place, new Date())) {
    if (!FARD.includes(p.name) || rem.prayers[p.name] === false) continue;
    const diff = now - p.time.getTime();
    const key = `qurba.adhan.${p.time.toISOString()}`;
    if (diff >= 0 && diff < 60_000 && !sessionStorage.getItem(key)) {
      sessionStorage.setItem(key, '1');
      playAdhan(p.name);
    }
  }
}

let started = false;
/** Call once from the app shell. */
export function initSounds() {
  if (started || typeof window === 'undefined') return;
  started = true;
  const firstTap = () => {
    window.removeEventListener('pointerdown', firstTap);
    window.removeEventListener('keydown', firstTap);
    if (soundPrefs.ambientOn && !busy()) playAmbient();
  };
  window.addEventListener('pointerdown', firstTap);
  window.addEventListener('keydown', firstTap);
  window.setInterval(checkAdhan, 20_000);
  checkAdhan();
}
