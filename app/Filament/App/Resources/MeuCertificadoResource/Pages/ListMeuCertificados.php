<?php

namespace App\Filament\App\Resources\MeuCertificadoResource\Pages;

use App\Filament\App\Resources\MeuCertificadoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMeuCertificados extends ListRecords
{
    protected static string $resource = MeuCertificadoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
