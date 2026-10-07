<?php

namespace App\Filament\Resources\ContentEntryResource\Pages;

use App\Filament\Resources\ContentEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageContentEntries extends ManageRecords
{
    protected static string $resource = ContentEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
