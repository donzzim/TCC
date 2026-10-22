<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\ResponsavelResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\ResponsavelResource;
use Filament\Resources\Pages\CreateRecord;

class CreateResponsavel extends CreateRecord
{
    protected static string $resource = ResponsavelResource::class;

    protected ?string $heading = 'Adicionar Responsável';
}
