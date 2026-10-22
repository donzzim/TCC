<?php

namespace App\Filament\Resources\Contratacao\OrcamentoEventoResource\Pages;

use App\Filament\Resources\Contratacao\OrcamentoEventoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrcamentoEvento extends EditRecord
{
    protected static string $resource = OrcamentoEventoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
