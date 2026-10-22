<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensIntangiveis\Resources\AmortizacaoResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensIntangiveis\Resources\AmortizacaoResource;
use App\Filament\Egap\Support\CreateActionModal;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAmortizacaos extends ListRecords
{
    protected static string $resource = AmortizacaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateActionModal::createAction(),
        ];
    }
}
