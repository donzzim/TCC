<?php

namespace App\Filament\Resources\Planejamento\IniciativasEstrategicasResource\Pages;

use App\Filament\Resources\Planejamento\IniciativasEstrategicasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIniciativasEstrategicas extends ListRecords
{
    protected static string $resource = IniciativasEstrategicasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
