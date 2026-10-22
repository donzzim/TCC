<?php

namespace App\Filament\Resources\Contratacao\PlanoOrcamentarioResource\Pages;

use App\Filament\Resources\Contratacao\PlanoOrcamentarioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlanoOrcamentarios extends ListRecords
{
    protected static string $resource = PlanoOrcamentarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
