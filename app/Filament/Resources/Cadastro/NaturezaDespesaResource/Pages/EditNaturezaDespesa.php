<?php

namespace App\Filament\Resources\Cadastro\NaturezaDespesaResource\Pages;

use App\Filament\Resources\Cadastro\NaturezaDespesaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNaturezaDespesa extends EditRecord
{
    protected static string $resource = NaturezaDespesaResource::class;

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
