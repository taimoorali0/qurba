<?php
// ===== QURBA config =====
return [
    'audio_base_url' => env('QURBA_AUDIO_BASE_URL', ''),
    'sync_reminder_days' => (int) env('QURBA_SYNC_REMINDER_DAYS', 7),
    'ui_locales' => ['en', 'ar', 'ur'],
    'vapid' => [
        'subject' => env('VAPID_SUBJECT', 'mailto:admin@example.com'),
        'public' => env('VAPID_PUBLIC_KEY'),
        'private' => env('VAPID_PRIVATE_KEY'),
    ],
    // Where deploy/backup.sh writes encrypted database backups (read by the admin System health card)
    'backup_dir' => env('QURBA_BACKUP_DIR', '/var/backups/qurba'),
    // Privacy-friendly, cookie-free page analytics. Leave the provider empty to turn analytics off.
    'analytics' => [
        'provider' => env('ANALYTICS_PROVIDER', ''),   // plausible | umami
        'script' => env('ANALYTICS_SCRIPT_URL', ''),   // e.g. https://plausible.io/js/script.js or https://umami.example.com/script.js
        'site' => env('ANALYTICS_SITE_ID', ''),        // Plausible: your domain; Umami: the website ID
    ],
    // Adhkar categories whose words get AI voice clips; the daily scheduled run fills in missing words
    // (free Gemini allows ~10 requests a day, so larger categories finish over several days).
    'word_voice_categories' => array_filter(explode(',', (string) env('QURBA_WORD_VOICE_CATEGORIES', 'kalimas'))),
];
