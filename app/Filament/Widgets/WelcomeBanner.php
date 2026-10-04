<?php
// ===== QURBA admin: greeting + quick actions =====
namespace App\Filament\Widgets;

use App\Support\AdminRoles;
use Filament\Widgets\Widget;

class WelcomeBanner extends Widget
{
    protected static string $view = 'filament.widgets.welcome-banner';
    protected static ?int $sort = -10;
    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $u = auth()->user();
        $hour = (int) now()->format('G');
        $hijri = '';
        if (class_exists(\IntlDateFormatter::class)) {
            $f = new \IntlDateFormatter('en@calendar=islamic-umalqura', \IntlDateFormatter::LONG, \IntlDateFormatter::NONE, config('app.timezone'), \IntlDateFormatter::TRADITIONAL);
            $hijri = (string) $f->format(now());
        }
        return [
            'name' => $u?->name ?? '',
            'role' => AdminRoles::LABELS[$u?->role] ?? '',
            'part' => $hour < 12 ? 'morning' : ($hour < 17 ? 'afternoon' : 'evening'),
            'date' => now()->format('l, j F Y'),
            'hijri' => $hijri,
        ];
    }
}
