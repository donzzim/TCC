<?php

namespace App\Filament\Egap\Interno\Groups\Cadastro\Resources\CentroCustoResource\Pages;

use App\Filament\Egap\Interno\Groups\Cadastro\Resources\CentroCustoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCentroCustos extends ListRecords
{
    protected static string $resource = CentroCustoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novo')
        ];
    }
}
