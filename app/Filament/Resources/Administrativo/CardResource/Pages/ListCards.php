<?php

namespace App\Filament\Resources\Administrativo\CardResource\Pages;

use App\Filament\Resources\Administrativo\CardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCards extends ListRecords
{
    protected static string $resource = CardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
