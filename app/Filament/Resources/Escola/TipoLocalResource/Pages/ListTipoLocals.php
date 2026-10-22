<?php

namespace App\Filament\Resources\Escola\TipoLocalResource\Pages;

use App\Filament\Resources\Escola\TipoLocalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTipoLocals extends ListRecords
{
    protected static string $resource = TipoLocalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
