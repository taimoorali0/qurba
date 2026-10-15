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
];
