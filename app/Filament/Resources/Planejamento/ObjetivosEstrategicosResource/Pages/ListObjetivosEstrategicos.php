<?php

namespace App\Filament\Resources\Planejamento\ObjetivosEstrategicosResource\Pages;

use App\Filament\Resources\Planejamento\ObjetivosEstrategicosResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListObjetivosEstrategicos extends ListRecords
{
    protected static string $resource = ObjetivosEstrategicosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
