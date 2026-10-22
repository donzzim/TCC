<?php

namespace App\Filament\Resources\Escola\TipoPublicoResource\Pages;

use App\Filament\Resources\Escola\TipoPublicoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTipoPublicos extends ListRecords
{
    protected static string $resource = TipoPublicoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
