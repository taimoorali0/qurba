<?php
// ===== QURBA admin dashboard numbers =====
namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as Base;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class QurbaStats extends Base
{
    protected static ?int $sort = 0;

    /** Daily counts for the last 7 days, oldest first */
    private function week(string $table, string $column): array
    {
        $rows = DB::table($table)->where($column, '>=', now()->startOfDay()->subDays(6))->pluck($column)
            ->countBy(fn ($v) => substr((string) $v, 0, 10));
        return collect(range(6, 0))->map(fn ($i) => $rows[now()->subDays($i)->toDateString()] ?? 0)->all();
    }

    protected function getStats(): array
    {
        $newUsers = $this->week('users', 'created_at');
        $syncs = $this->week('sync_logs', 'synced_at');
        $interests = $this->week('learn_interests', 'created_at');
        $devices = DB::table('user_devices')->count();
        $push = DB::table('push_devices')->count();

        return [
            Stat::make('Accounts', number_format(User::count()))
                ->description(array_sum($newUsers) . ' new this week')->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($newUsers)->color('primary'),
            Stat::make('Synced devices', number_format($devices))
                ->description(number_format(array_sum($syncs)) . ' syncs this week')->descriptionIcon('heroicon-m-arrow-path')
                ->chart($syncs)->color('success'),
            Stat::make('Reminder subscribers', number_format($push))
                ->description('Devices receiving prayer reminders')->descriptionIcon('heroicon-m-bell-alert')->color('warning'),
            Stat::make('Class requests', number_format(DB::table('learn_interests')->count()))
                ->description(array_sum($interests) . ' this week')->descriptionIcon('heroicon-m-academic-cap')
                ->chart($interests)->color('primary'),
        ];
    }
}
