<?php

namespace App\Filament\Resources\Escola\TipoDocumentoResource\Pages;

use App\Filament\Resources\Escola\TipoDocumentoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTipoDocumento extends EditRecord
{
    protected static string $resource = TipoDocumentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
