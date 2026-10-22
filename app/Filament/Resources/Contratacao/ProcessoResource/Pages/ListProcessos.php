<?php

namespace App\Filament\Resources\Contratacao\ProcessoResource\Pages;

use App\Filament\Resources\Contratacao\ProcessoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProcessos extends ListRecords
{
    protected static string $resource = ProcessoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
