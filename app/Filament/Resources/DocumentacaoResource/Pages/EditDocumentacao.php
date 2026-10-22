<?php

namespace App\Filament\Resources\DocumentacaoResource\Pages;

use App\Filament\Resources\DocumentacaoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDocumentacao extends EditRecord
{
    protected static string $resource = DocumentacaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
