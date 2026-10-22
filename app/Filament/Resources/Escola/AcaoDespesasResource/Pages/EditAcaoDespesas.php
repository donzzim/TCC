<?php

namespace App\Filament\Resources\Escola\AcaoDespesasResource\Pages;

use App\Filament\Resources\Escola\AcaoDespesasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAcaoDespesas extends EditRecord
{
    protected static string $resource = AcaoDespesasResource::class;

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
