<?php

namespace App\Filament\Resources\Escola\TipoPublicoResource\Pages;

use App\Filament\Resources\Escola\TipoPublicoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTipoPublico extends EditRecord
{
    protected static string $resource = TipoPublicoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
