<?php
namespace App\Filament\Resources\QuranReciterResource\Pages;

use App\Filament\Resources\QuranReciterResource;
use Filament\Resources\Pages\ManageRecords;

class ManageQuranReciters extends ManageRecords
{
    protected static string $resource = QuranReciterResource::class;

    protected function getHeaderActions(): array
    {
        return [\Filament\Actions\CreateAction::make()];
    }
}
