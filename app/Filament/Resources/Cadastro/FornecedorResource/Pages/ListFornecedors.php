<?php

namespace App\Filament\Resources\Cadastro\FornecedorResource\Pages;

use App\Filament\Resources\Cadastro\FornecedorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFornecedors extends ListRecords
{
    protected static string $resource = FornecedorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
