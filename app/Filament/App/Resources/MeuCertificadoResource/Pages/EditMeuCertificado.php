<?php

namespace App\Filament\App\Resources\MeuCertificadoResource\Pages;

use App\Filament\App\Resources\MeuCertificadoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMeuCertificado extends EditRecord
{
    protected static string $resource = MeuCertificadoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
