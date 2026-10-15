<?php
// ===== QURBA admin: is the server doing its jobs? (backups, reminders, queue, disk) =====
namespace App\Filament\Widgets;

use App\Support\AdminRoles;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SystemHealth extends Widget
{
    protected static string $view = 'filament.widgets.system-health';
    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return AdminRoles::is(AdminRoles::SUPER);
    }

    /** Newest backup file in the daily folder, or null when none can be read. */
    private function lastBackup(): ?Carbon
    {
        $files = @glob(rtrim((string) config('qurba.backup_dir'), '/').'/daily/*.enc') ?: [];
        $newest = collect($files)->map(fn ($f) => @filemtime($f) ?: 0)->max();
        return $newest ? Carbon::createFromTimestamp($newest) : null;
    }

    protected function getViewData(): array
    {
        $backup = $this->lastBackup();
        // Reminders more than 10 minutes late mean the scheduler (cron) or queue is not running.
        $late = DB::table('reminder_jobs')->whereNull('sent_at')->where('fire_at', '<', now()->subMinutes(10))->where('fire_at', '>', now()->subDay())->count();
        $lastSent = DB::table('reminder_jobs')->max('sent_at');
        $failed = DB::table('failed_jobs')->count();
        $queued = DB::table('jobs')->count();
        $free = @disk_free_space(base_path()) ?: 0;
        $total = @disk_total_space(base_path()) ?: 0;
        $freePct = $total ? round($free / $total * 100) : null;

        return ['rows' => [
            ['Last database backup', $backup ? $backup->diffForHumans() : 'None found', $backup && $backup->gt(now()->subHours(36))],
            ['Late reminders (last 24 h)', $late ? "{$late} not sent" : 'None', $late === 0],
            ['Last reminder sent', $lastSent ? Carbon::parse($lastSent)->diffForHumans() : 'Never', true],
            ['Failed background jobs', (string) $failed, $failed === 0],
            ['Jobs waiting in queue', (string) $queued, $queued < 100],
            ['Free disk space', $freePct === null ? 'Unknown' : round($free / 1073741824, 1)." GB ({$freePct}%)", $freePct === null || $freePct >= 10],
            ['App version', trim((string) @shell_exec('git -C '.escapeshellarg(base_path()).' rev-parse --short HEAD 2>/dev/null')) ?: 'Unknown', true],
        ]];
    }
}
