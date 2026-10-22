<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\DenominacaoResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\DenominacaoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDenominacao extends CreateRecord
{
    protected static string $resource = DenominacaoResource::class;

    protected ?string $heading = 'Adicionar Denominação';
}
