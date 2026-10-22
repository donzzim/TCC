<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\EntradaSaidaResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\EntradaSaidaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEntradaSaida extends CreateRecord
{
    protected static string $resource = EntradaSaidaResource::class;

    protected ?string $heading = 'Adicionar Entrada/Saída';
}
