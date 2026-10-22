<?php

namespace App\Filament\Resources\Escola\AcaoResource\Pages;

use App\Filament\Resources\Escola\AcaoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAcaos extends ListRecords
{
    protected static string $resource = AcaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
