<?php

namespace App\Filament\Resources\Contratacao\ContratacaoModeloResource\Pages;

use App\Filament\Resources\Contratacao\ContratacaoModeloResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContratacaoModelo extends EditRecord
{
    protected static string $resource = ContratacaoModeloResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
