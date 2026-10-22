<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\CondicaoUsoResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\CondicaoUsoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCondicaoUso extends CreateRecord
{
    protected static string $resource = CondicaoUsoResource::class;

    protected ?string $heading = 'Adicionar Condição de Uso';
}
