<?php
// ===== QURBA admin dashboard numbers =====
namespace App\Filament\Widgets;

use App\Models\ContentSource;
use App\Models\QuranAyah;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as Base;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class QurbaStats extends Base
{
    protected function getStats(): array
    {
        $ayahs = QuranAyah::count();
        $pending = ContentSource::where('status', 'pending_review')->count();
        return [
            Stat::make('Accounts', number_format(User::count())),
            Stat::make('Synced devices', number_format(DB::table('user_devices')->count())),
            Stat::make('Syncs in last 24h', number_format(DB::table('sync_logs')->where('synced_at', '>=', now()->subDay())->count())),
            Stat::make('Sources awaiting review', $pending)->color($pending ? 'warning' : 'success')
                ->description($pending ? 'Not visible in production until approved' : 'All reviewed'),
            Stat::make('Quran ayahs', number_format($ayahs))->color($ayahs === 6236 ? 'success' : 'danger')
                ->description($ayahs === 6236 ? 'Complete — run integrity check in Quran text' : 'Incomplete'),
        ];
    }
}
