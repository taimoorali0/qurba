<?php
// ===== QURBA admin: new accounts per day =====
namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;

class SignupsChart extends ChartWidget
{
    protected static ?string $heading = 'New accounts';
    protected static ?int $sort = 3;
    protected static ?string $maxHeight = '260px';
    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return ['7' => 'Last 7 days', '30' => 'Last 30 days', '90' => 'Last 90 days'];
    }

    protected function getData(): array
    {
        $days = (int) $this->filter;
        $from = now()->startOfDay()->subDays($days - 1);
        $counts = User::where('created_at', '>=', $from)->get(['created_at'])
            ->countBy(fn ($u) => $u->created_at->toDateString());
        $labels = [];
        $data = [];
        for ($d = $from->copy(); $d->lte(now()); $d->addDay()) {
            $labels[] = $d->format('j M');
            $data[] = $counts[$d->toDateString()] ?? 0;
        }
        return [
            'datasets' => [[
                'label' => 'Accounts', 'data' => $data, 'fill' => true, 'tension' => 0.35,
                'borderColor' => '#14654D', 'backgroundColor' => 'rgba(201,162,74,0.18)', 'pointRadius' => 0,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
