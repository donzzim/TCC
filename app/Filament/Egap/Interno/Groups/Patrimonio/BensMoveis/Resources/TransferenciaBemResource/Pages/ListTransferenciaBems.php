<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\TransferenciaBemResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\TransferenciaBemResource;
use Filament\Actions\CreateAction;
use App\Filament\Egap\Support\ContagemDaTabelaEmCache;
use Filament\Resources\Pages\ListRecords;

class ListTransferenciaBems extends ListRecords
{
    use ContagemDaTabelaEmCache;

    protected static string $resource = TransferenciaBemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novo'),
        ];
    }
}
