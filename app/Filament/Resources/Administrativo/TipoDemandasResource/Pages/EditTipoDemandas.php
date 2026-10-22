<?php

namespace App\Filament\Resources\Administrativo\TipoDemandasResource\Pages;

use App\Filament\Resources\Administrativo\TipoDemandasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTipoDemandas extends EditRecord
{
    protected static string $resource = TipoDemandasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
