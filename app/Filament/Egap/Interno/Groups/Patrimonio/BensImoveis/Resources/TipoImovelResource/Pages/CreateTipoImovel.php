<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\TipoImovelResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\TipoImovelResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTipoImovel extends CreateRecord
{
    protected static string $resource = TipoImovelResource::class;

    protected ?string $heading = 'Adicionar Tipo de Imóvel';
}
