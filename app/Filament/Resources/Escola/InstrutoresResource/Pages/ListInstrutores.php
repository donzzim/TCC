<?php

namespace App\Filament\Resources\Escola\InstrutoresResource\Pages;

use App\Filament\Resources\Escola\InstrutoresResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInstrutores extends ListRecords
{
    protected static string $resource = InstrutoresResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
