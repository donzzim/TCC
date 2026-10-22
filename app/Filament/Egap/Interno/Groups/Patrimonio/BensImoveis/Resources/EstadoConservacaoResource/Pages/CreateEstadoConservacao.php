<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\EstadoConservacaoResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\EstadoConservacaoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEstadoConservacao extends CreateRecord
{
    protected static string $resource = EstadoConservacaoResource::class;

    protected ?string $heading = 'Adicionar Estado de Conservação';
}
