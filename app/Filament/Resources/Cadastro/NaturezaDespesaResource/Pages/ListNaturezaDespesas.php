<?php

namespace App\Filament\Resources\Cadastro\NaturezaDespesaResource\Pages;

use App\Filament\Resources\Cadastro\NaturezaDespesaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNaturezaDespesas extends ListRecords
{
    protected static string $resource = NaturezaDespesaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
