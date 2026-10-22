<?php

namespace App\Filament\Resources\Planejamento\TemasEstrategicosResource\Pages;

use App\Filament\Resources\Planejamento\TemasEstrategicosResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTemasEstrategicos extends ListRecords
{
    protected static string $resource = TemasEstrategicosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
