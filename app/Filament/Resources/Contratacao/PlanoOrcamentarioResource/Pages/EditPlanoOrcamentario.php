<?php

namespace App\Filament\Resources\Contratacao\PlanoOrcamentarioResource\Pages;

use App\Filament\Resources\Contratacao\PlanoOrcamentarioResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlanoOrcamentario extends EditRecord
{
    protected static string $resource = PlanoOrcamentarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
