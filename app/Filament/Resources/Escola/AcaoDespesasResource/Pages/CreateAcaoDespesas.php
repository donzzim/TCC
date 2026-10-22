<?php

namespace App\Filament\Resources\Escola\AcaoDespesasResource\Pages;

use App\Filament\Resources\Escola\AcaoDespesasResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAcaoDespesas extends CreateRecord
{
    protected static string $resource = AcaoDespesasResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
