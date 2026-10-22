<?php

namespace App\Filament\Resources\Escola\InstituicoesResource\Pages;

use App\Filament\Resources\Escola\InstituicoesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInstituicoes extends ListRecords
{
    protected static string $resource = InstituicoesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
