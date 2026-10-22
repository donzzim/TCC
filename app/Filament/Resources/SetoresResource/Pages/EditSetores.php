<?php

namespace App\Filament\Resources\SetoresResource\Pages;

use App\Filament\Resources\SetoresResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSetores extends EditRecord
{
    protected static string $resource = SetoresResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
