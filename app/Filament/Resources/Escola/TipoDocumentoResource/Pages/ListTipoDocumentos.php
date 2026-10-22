<?php

namespace App\Filament\Resources\Escola\TipoDocumentoResource\Pages;

use App\Filament\Resources\Escola\TipoDocumentoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTipoDocumentos extends ListRecords
{
    protected static string $resource = TipoDocumentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
