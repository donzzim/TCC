<?php

namespace App\Filament\App\Resources\MeusCursosResource\Pages;

use App\Filament\App\Resources\MeusCursosResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMeusCursos extends ListRecords
{
    protected static string $resource = MeusCursosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
