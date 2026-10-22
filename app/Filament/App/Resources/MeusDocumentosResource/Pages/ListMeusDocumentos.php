<?php

namespace App\Filament\App\Resources\MeusDocumentosResource\Pages;

use App\Filament\App\Resources\MeusDocumentosResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMeusDocumentos extends ListRecords
{
    protected static string $resource = MeusDocumentosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
