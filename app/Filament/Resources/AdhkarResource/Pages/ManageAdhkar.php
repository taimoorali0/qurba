<?php
namespace App\Filament\Resources\AdhkarResource\Pages;

use App\Filament\Resources\AdhkarResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAdhkar extends ManageRecords
{
    protected static string $resource = AdhkarResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
