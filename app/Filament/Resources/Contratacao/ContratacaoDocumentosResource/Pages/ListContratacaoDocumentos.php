<?php

namespace App\Filament\Resources\Contratacao\ContratacaoDocumentosResource\Pages;

use App\Filament\Resources\Contratacao\ContratacaoDocumentosResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContratacaoDocumentos extends ListRecords
{
    protected static string $resource = ContratacaoDocumentosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
