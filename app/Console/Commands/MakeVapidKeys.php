<?php
// ===== QURBA: generate Web Push (VAPID) keys once =====
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class MakeVapidKeys extends Command
{
    protected $signature = 'qurba:vapid';
    protected $description = 'Generate VAPID keys for push notifications (add them to .env)';

    public function handle(): int
    {
        if (config('qurba.vapid.public')) {
            $this->warn('VAPID keys already exist in .env. Replacing them breaks every existing subscription.');
            if (! $this->confirm('Generate new keys anyway?')) return self::SUCCESS;
        }
        $k = VAPID::createVapidKeys();
        $this->line("\nAdd to .env:\n");
        $this->line('VAPID_SUBJECT=mailto:you@your-domain');
        $this->line('VAPID_PUBLIC_KEY=' . $k['publicKey']);
        $this->line('VAPID_PRIVATE_KEY=' . $k['privateKey']);
        $this->line("\nKeep the private key secret. Use the same keys on every server.");
        return self::SUCCESS;
    }
}
