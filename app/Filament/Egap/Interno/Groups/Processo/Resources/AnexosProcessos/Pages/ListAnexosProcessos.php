<?php

namespace App\Filament\Egap\Interno\Groups\Processo\Resources\AnexosProcessos\Pages;

use App\Filament\Egap\Interno\Groups\Processo\Resources\AnexosProcessos\AnexosProcessoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAnexosProcessos extends ListRecords
{
    protected static string $resource = AnexosProcessoResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
