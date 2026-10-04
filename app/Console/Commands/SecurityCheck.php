<?php
// ===== QURBA: pre-launch security checklist =====
namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SecurityCheck extends Command
{
    protected $signature = 'qurba:security-check';
    protected $description = 'Check production settings before going live';

    public function handle(): int
    {
        $rows = [];
        $add = function (string $item, bool $ok, string $fix) use (&$rows) { $rows[] = [$ok ? 'OK' : 'FIX', $item, $ok ? '' : $fix]; };

        $add('APP_ENV=production', app()->isProduction(), 'Set APP_ENV=production');
        $add('APP_DEBUG=false', ! config('app.debug'), 'Set APP_DEBUG=false');
        $add('APP_URL uses https', str_starts_with(config('app.url'), 'https://'), 'Set APP_URL=https://your-domain');
        $add('APP_KEY set', (bool) config('app.key'), 'Run php artisan key:generate');
        $add('Secure session cookie', (bool) config('session.secure'), 'Set SESSION_SECURE_COOKIE=true');
        $add('Session cookie http-only', (bool) config('session.http_only'), 'Keep SESSION_HTTP_ONLY=true');
        $add('Database user is not root', config('database.connections.' . config('database.default') . '.username') !== 'root', 'Create a least-privilege MySQL user for Qurba');
        $add('Database password set', (bool) config('database.connections.' . config('database.default') . '.password'), 'Set DB_PASSWORD');
        $admins = User::whereNotNull('role')->get();
        $add('All admins have 2FA', $admins->every(fn ($u) => $u->two_factor_confirmed_at), 'Each admin must sign in to /admin and finish 2FA setup');
        $add('Quran text complete', DB::table('quran_ayahs')->count() === 6236, 'Import and verify the Quran text');
        $pending = DB::table('content_sources')->where('status', 'pending_review')->count();
        $add('No sources awaiting review', $pending === 0, "{$pending} source(s) still pending: approve or reject in /admin");
        $add('Queue is not "sync"', config('queue.default') !== 'sync', 'Use QUEUE_CONNECTION=database (or redis) with a worker');

        $this->table(['', 'Check', 'How to fix'], $rows);
        $bad = collect($rows)->where(0, 'FIX')->count();
        $bad ? $this->warn("{$bad} item(s) to fix before launch.") : $this->info('Ready for launch.');
        return $bad ? self::FAILURE : self::SUCCESS;
    }
}
