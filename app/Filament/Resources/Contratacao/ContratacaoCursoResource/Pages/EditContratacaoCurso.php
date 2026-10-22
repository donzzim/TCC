<?php

namespace App\Filament\Resources\Contratacao\ContratacaoCursoResource\Pages;

use App\Filament\Resources\Contratacao\ContratacaoCursoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContratacaoCurso extends EditRecord
{
    protected static string $resource = ContratacaoCursoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
