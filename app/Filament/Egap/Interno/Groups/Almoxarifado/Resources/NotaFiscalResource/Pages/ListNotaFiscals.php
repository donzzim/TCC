<?php

namespace App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\NotaFiscalResource\Pages;

use App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\NotaFiscalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNotaFiscals extends ListRecords
{
    protected static string $resource = NotaFiscalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novo')
        ];
    }
}
