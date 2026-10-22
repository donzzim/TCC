<?php

namespace App\Filament\Resources\Cadastro\NaturezaDespesaResource\Pages;

use App\Filament\Resources\Cadastro\NaturezaDespesaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateNaturezaDespesa extends CreateRecord
{
    protected static string $resource = NaturezaDespesaResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
