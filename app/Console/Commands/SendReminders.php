<?php
// ===== QURBA: send due reminders (scheduler runs this every minute) =====
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class SendReminders extends Command
{
    protected $signature = 'qurba:send-reminders';
    protected $description = 'Send push reminders that are due now';

    public function handle(): int
    {
        $v = config('qurba.vapid');
        if (! $v['public'] || ! $v['private']) { $this->warn('VAPID keys missing; nothing sent.'); return self::SUCCESS; }

        $jobs = DB::table('reminder_jobs')->join('push_devices', 'push_devices.id', '=', 'reminder_jobs.push_device_id')
            ->whereNull('reminder_jobs.sent_at')
            ->where('reminder_jobs.fire_at', '<=', now())
            ->where('reminder_jobs.fire_at', '>=', now()->subMinutes(10))   // never send stale reminders
            ->limit(1000)
            ->get(['reminder_jobs.*', 'push_devices.endpoint', 'push_devices.p256dh', 'push_devices.auth']);
        if ($jobs->isEmpty()) return self::SUCCESS;

        $push = new WebPush(['VAPID' => ['subject' => $v['subject'], 'publicKey' => $v['public'], 'privateKey' => $v['private']]], ['TTL' => 600, 'urgency' => 'high']);
        $byEndpoint = [];
        foreach ($jobs as $j) {
            $push->queueNotification(
                Subscription::create(['endpoint' => $j->endpoint, 'publicKey' => $j->p256dh, 'authToken' => $j->auth, 'contentEncoding' => 'aes128gcm']),
                json_encode(['title' => $j->title, 'body' => $j->body, 'url' => $j->url, 'tag' => $j->kind], JSON_UNESCAPED_UNICODE),
            );
            $byEndpoint[$j->endpoint][] = $j->id;
        }
        DB::table('reminder_jobs')->whereIn('id', $jobs->pluck('id'))->update(['sent_at' => now()]);
        DB::table('reminder_jobs')->whereNull('sent_at')->where('fire_at', '<', now()->subMinutes(10))->delete();
        DB::table('reminder_jobs')->whereNotNull('sent_at')->where('fire_at', '<', now()->subDays(2))->delete();

        $sent = 0; $gone = 0;
        foreach ($push->flush() as $report) {
            if ($report->isSuccess()) { $sent++; continue; }
            if ($report->isSubscriptionExpired()) {      // 404/410: the browser removed this subscription
                DB::table('push_devices')->where('endpoint', $report->getEndpoint())->delete();
                $gone++;
            }
        }
        $this->info("Sent {$sent}, removed {$gone} expired.");
        return self::SUCCESS;
    }
}
