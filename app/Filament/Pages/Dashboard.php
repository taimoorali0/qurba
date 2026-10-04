<?php
// ===== QURBA admin home =====
namespace App\Filament\Pages;

use App\Filament\Widgets;
use Filament\Pages\Dashboard as Base;

class Dashboard extends Base
{
    protected static ?string $title = 'Dashboard';

    public function getColumns(): int|string|array
    {
        return ['md' => 2, 'xl' => 3];
    }

    public function getWidgets(): array
    {
        return [
            Widgets\WelcomeBanner::class,
            Widgets\QurbaStats::class,
            Widgets\ReviewQueue::class,
            Widgets\ContentHealth::class,
            Widgets\SignupsChart::class,
            Widgets\RecentActivity::class,
        ];
    }
}
