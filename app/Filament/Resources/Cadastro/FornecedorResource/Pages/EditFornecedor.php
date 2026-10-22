<?php

namespace App\Filament\Resources\Cadastro\FornecedorResource\Pages;

use App\Filament\Resources\Cadastro\FornecedorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFornecedor extends EditRecord
{
    protected static string $resource = FornecedorResource::class;

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
