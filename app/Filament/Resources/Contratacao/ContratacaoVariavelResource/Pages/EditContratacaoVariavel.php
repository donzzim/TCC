<?php

namespace App\Filament\Resources\Contratacao\ContratacaoVariavelResource\Pages;

use App\Filament\Resources\Contratacao\ContratacaoVariavelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContratacaoVariavel extends EditRecord
{
    protected static string $resource = ContratacaoVariavelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
