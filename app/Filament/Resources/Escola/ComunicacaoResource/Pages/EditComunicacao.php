<?php

namespace App\Filament\Resources\Escola\ComunicacaoResource\Pages;

use App\Filament\Resources\Escola\ComunicacaoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditComunicacao extends EditRecord
{
    protected static string $resource = ComunicacaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
