<?php

namespace App\Filament\Resources\Escola\InstituicoesResource\Pages;

use App\Filament\Resources\Escola\InstituicoesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInstituicoes extends EditRecord
{
    protected static string $resource = InstituicoesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
