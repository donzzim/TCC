<?php

namespace App\Filament\Resources\Contratacao\PlanoContratacaoResource\Pages;

use App\Filament\Resources\Contratacao\PlanoContratacaoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlanoContratacao extends EditRecord
{
    protected static string $resource = PlanoContratacaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
