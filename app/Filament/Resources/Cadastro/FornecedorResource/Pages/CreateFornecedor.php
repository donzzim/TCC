<?php

namespace App\Filament\Resources\Cadastro\FornecedorResource\Pages;

use App\Filament\Resources\Cadastro\FornecedorResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFornecedor extends CreateRecord
{
    protected static string $resource = FornecedorResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
