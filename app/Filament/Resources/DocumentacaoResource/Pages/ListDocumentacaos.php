<?php

namespace App\Filament\Resources\DocumentacaoResource\Pages;

use App\Filament\Resources\DocumentacaoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDocumentacaos extends ListRecords
{
    protected static string $resource = DocumentacaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
