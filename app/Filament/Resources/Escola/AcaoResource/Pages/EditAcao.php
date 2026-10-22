<?php

namespace App\Filament\Resources\Escola\AcaoResource\Pages;

use App\Filament\Resources\Escola\AcaoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAcao extends EditRecord
{
    protected static string $resource = AcaoResource::class;

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
