<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\SituacaoResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\SituacaoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSituacao extends CreateRecord
{
    protected static string $resource = SituacaoResource::class;

    protected ?string $heading = 'Adicionar Situação';
}
