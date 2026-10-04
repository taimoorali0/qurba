// ===== QURBA: user location (device or manual) shared by Prayer + Qibla =====
import { reactive, watch } from 'vue';

export interface Place { lat: number; lng: number; label: string; tz: string; country: string; mode: 'device' | 'manual' }

export const CITIES: Omit<Place, 'mode'>[] = [
  { label: 'Lahore', lat: 31.5204, lng: 74.3587, tz: 'Asia/Karachi', country: 'PK' },
  { label: 'Karachi', lat: 24.8607, lng: 67.0011, tz: 'Asia/Karachi', country: 'PK' },
  { label: 'Islamabad', lat: 33.6844, lng: 73.0479, tz: 'Asia/Karachi', country: 'PK' },
  { label: 'Faisalabad', lat: 31.4504, lng: 73.1350, tz: 'Asia/Karachi', country: 'PK' },
  { label: 'Makkah', lat: 21.3891, lng: 39.8579, tz: 'Asia/Riyadh', country: 'SA' },
  { label: 'Madinah', lat: 24.5247, lng: 39.5692, tz: 'Asia/Riyadh', country: 'SA' },
  { label: 'Riyadh', lat: 24.7136, lng: 46.6753, tz: 'Asia/Riyadh', country: 'SA' },
  { label: 'Jeddah', lat: 21.4858, lng: 39.1925, tz: 'Asia/Riyadh', country: 'SA' },
  { label: 'Dammam', lat: 26.4207, lng: 50.0888, tz: 'Asia/Riyadh', country: 'SA' },
  { label: 'Dubai', lat: 25.2048, lng: 55.2708, tz: 'Asia/Dubai', country: 'AE' },
  { label: 'London', lat: 51.5072, lng: -0.1276, tz: 'Europe/London', country: 'GB' },
  { label: 'Birmingham', lat: 52.4862, lng: -1.8904, tz: 'Europe/London', country: 'GB' },
  { label: 'New York', lat: 40.7128, lng: -74.0060, tz: 'America/New_York', country: 'US' },
  { label: 'Houston', lat: 29.7604, lng: -95.3698, tz: 'America/Chicago', country: 'US' },
];

function load(): Place | null { try { const v = localStorage.getItem('qurba.location'); return v ? JSON.parse(v) : null; } catch { return null; } }
export const loc = reactive<{ place: Place | null; error: string; busy: boolean }>({ place: load(), error: '', busy: false });
watch(() => loc.place, (v) => { try { v ? localStorage.setItem('qurba.location', JSON.stringify(v)) : localStorage.removeItem('qurba.location'); } catch {} }, { deep: true });

export const deviceTz = () => Intl.DateTimeFormat().resolvedOptions().timeZone;

/** Ask the browser for location. Resolves true on success; sets loc.error otherwise. */
export function useDeviceLocation(): Promise<boolean> {
  loc.error = '';
  if (!('geolocation' in navigator)) { loc.error = 'unsupported'; return Promise.resolve(false); }
  loc.busy = true;
  return new Promise((resolve) => {
    navigator.geolocation.getCurrentPosition(
      (p) => {
        loc.busy = false;
        loc.place = { lat: +p.coords.latitude.toFixed(4), lng: +p.coords.longitude.toFixed(4),
          label: `${p.coords.latitude.toFixed(2)}, ${p.coords.longitude.toFixed(2)}`, tz: deviceTz(),
          country: loc.place?.country ?? '', mode: 'device' };
        resolve(true);
      },
      (e) => { loc.busy = false; loc.error = e.code === 1 ? 'denied' : 'unavailable'; resolve(false); },
      { enableHighAccuracy: false, timeout: 15000, maximumAge: 10 * 60 * 1000 },
    );
  });
}

export function setCity(c: Omit<Place, 'mode'>) { loc.place = { ...c, mode: 'manual' }; loc.error = ''; }
export function setManual(lat: number, lng: number) {
  if (Math.abs(lat) > 90 || Math.abs(lng) > 180) return false;
  loc.place = { lat, lng, label: `${lat.toFixed(2)}, ${lng.toFixed(2)}`, tz: deviceTz(), country: '', mode: 'manual' };
  return true;
}
