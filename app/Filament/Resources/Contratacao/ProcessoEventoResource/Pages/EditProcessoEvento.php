<?php

namespace App\Filament\Resources\Contratacao\ProcessoEventoResource\Pages;

use App\Filament\Resources\Contratacao\ProcessoEventoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProcessoEvento extends EditRecord
{
    protected static string $resource = ProcessoEventoResource::class;

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
