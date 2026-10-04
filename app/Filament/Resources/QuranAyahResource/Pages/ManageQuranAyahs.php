<?php
namespace App\Filament\Resources\QuranAyahResource\Pages;

use App\Filament\Resources\QuranAyahResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Artisan;

class ManageQuranAyahs extends ManageRecords
{
    protected static string $resource = QuranAyahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('verify')->label('Run integrity check')->icon('heroicon-o-shield-check')
                ->action(function () {
                    $code = Artisan::call('qurba:verify-quran');
                    $out = trim(Artisan::output());
                    Notification::make()->title($code === 0 ? 'Quran text verified' : 'Integrity check FAILED')
                        ->body(nl2br(e(mb_substr($out, 0, 1500))))->{$code === 0 ? 'success' : 'danger'}()->persistent()->send();
                }),
        ];
    }
}
