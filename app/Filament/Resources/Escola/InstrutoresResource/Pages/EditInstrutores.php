<?php

namespace App\Filament\Resources\Escola\InstrutoresResource\Pages;

use App\Filament\Resources\Escola\InstrutoresResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInstrutores extends EditRecord
{
    protected static string $resource = InstrutoresResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
