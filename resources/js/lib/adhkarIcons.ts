// ===== QURBA: one icon per adhkar/dua category (shared by the list and category pages) =====
import type { Component } from 'vue';
import { BookHeart, Brain, CloudRain, HandHeart, HeartPulse, House, Landmark, Moon, Plane, Shield, Sparkles, Sun, Sunset, Undo2, Utensils, Wind } from 'lucide-vue-next';

const ICONS: Record<string, Component> = {
  morning: Sun, evening: Sunset, 'after-salah': Sparkles, 'sleep-wake': Moon, travel: Plane, 'daily-duas': HandHeart, 'quranic-duas': BookHeart,
  illness: HeartPulse, distress: Wind, knowledge: Brain, forgiveness: Undo2, protection: Shield, 'home-family': House,
  'food-drink': Utensils, weather: CloudRain, masjid: Landmark,
};
export const categoryIcon = (slug: string): Component => ICONS[slug] ?? Sparkles;
