<?php

namespace App\Filament\Resources\Escola\TipoLocalResource\Pages;

use App\Filament\Resources\Escola\TipoLocalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTipoLocal extends EditRecord
{
    protected static string $resource = TipoLocalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
