<?php

namespace App\Filament\Resources\SetoresResource\Pages;

use App\Filament\Resources\SetoresResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSetores extends ListRecords
{
    protected static string $resource = SetoresResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
