<?php

namespace App\Filament\Resources\Escola\CertificadoModeloResource\Pages;

use App\Filament\Resources\Escola\CertificadoModeloResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCertificadoModelos extends ListRecords
{
    protected static string $resource = CertificadoModeloResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
