// ===== QURBA tailwind.config.js (Tailwind v3) — FULL FILE =====
import defaultTheme from 'tailwindcss/defaultTheme';

import animate from 'tailwindcss-animate';
import forms from '@tailwindcss/forms';
/** @type {import('tailwindcss').Config} */
export default {
    darkMode: ['class'],
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{vue,ts,js}',
    ],
    theme: {
        extend: {
            boxShadow: { soft: '0 1px 2px rgba(16,24,20,.04), 0 8px 24px rgba(16,24,20,.05)', lift: '0 10px 30px rgba(16,24,20,.08)' },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                display: ['Marcellus', 'Georgia', 'serif'],
                quran: ['"Amiri Quran"', 'Amiri', 'serif'],
            },
            borderRadius: {
                lg: 'var(--radius)',
                md: 'calc(var(--radius) - 2px)',
                sm: 'calc(var(--radius) - 4px)',
            },
            colors: {
                // ===== QURBA BRAND =====
                emerald: { 950: '#072A20', 900: '#0B3B2D', 700: '#14654D', 500: '#2E8B6B', 100: '#DCEDE5' },
                gold: { 600: '#A9822F', 500: '#C9A24A', 200: '#EEDDB0' },
                cream: '#F8F7F2',
                paper: '#FFFFFF',
                ink: { DEFAULT: '#1E2925', soft: '#5B6862' },
                line: '#E9EBE4',
                mint: '#E7F4EE', sand: '#FBF3E1', sky: '#E8F1FB', rose: '#FBECEA', lilac: '#EFEBFA',
                // ===== STARTER KIT (login/dashboard) =====
                background: 'hsl(var(--background))',
                foreground: 'hsl(var(--foreground))',
                card: { DEFAULT: 'hsl(var(--card))', foreground: 'hsl(var(--card-foreground))' },
                popover: { DEFAULT: 'hsl(var(--popover))', foreground: 'hsl(var(--popover-foreground))' },
                primary: { DEFAULT: 'hsl(var(--primary))', foreground: 'hsl(var(--primary-foreground))' },
                secondary: { DEFAULT: 'hsl(var(--secondary))', foreground: 'hsl(var(--secondary-foreground))' },
                muted: { DEFAULT: 'hsl(var(--muted))', foreground: 'hsl(var(--muted-foreground))' },
                accent: { DEFAULT: 'hsl(var(--accent))', foreground: 'hsl(var(--accent-foreground))' },
                destructive: { DEFAULT: 'hsl(var(--destructive))', foreground: 'hsl(var(--destructive-foreground))' },
                border: 'hsl(var(--border))',
                input: 'hsl(var(--input))',
                ring: 'hsl(var(--ring))',
                chart: {
                    1: 'hsl(var(--chart-1))', 2: 'hsl(var(--chart-2))', 3: 'hsl(var(--chart-3))',
                    4: 'hsl(var(--chart-4))', 5: 'hsl(var(--chart-5))',
                },
                sidebar: {
                    DEFAULT: 'hsl(var(--sidebar-background))',
                    foreground: 'hsl(var(--sidebar-foreground))',
                    primary: 'hsl(var(--sidebar-primary))',
                    'primary-foreground': 'hsl(var(--sidebar-primary-foreground))',
                    accent: 'hsl(var(--sidebar-accent))',
                    'accent-foreground': 'hsl(var(--sidebar-accent-foreground))',
                    border: 'hsl(var(--sidebar-border))',
                    ring: 'hsl(var(--sidebar-ring))',
                },
            },
        },
    },
    plugins: [animate, forms],
};
