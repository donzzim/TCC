<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\ItemInventarioResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\ItemInventarioResource;
use Filament\Actions\CreateAction;
use App\Filament\Egap\Support\ContagemDaTabelaEmCache;
use Filament\Resources\Pages\ListRecords;

class ListItemInventarios extends ListRecords
{
    use ContagemDaTabelaEmCache;

    protected static string $resource = ItemInventarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novo'),
        ];
    }
}
