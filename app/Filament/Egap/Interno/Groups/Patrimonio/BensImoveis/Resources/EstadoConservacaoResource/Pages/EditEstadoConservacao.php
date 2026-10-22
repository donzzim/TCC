<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\EstadoConservacaoResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\EstadoConservacaoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEstadoConservacao extends EditRecord
{
    protected static string $resource = EstadoConservacaoResource::class;

    protected ?string $heading = 'Editar Estado de Conservação';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('Excluir'),
        ];
    }
}
