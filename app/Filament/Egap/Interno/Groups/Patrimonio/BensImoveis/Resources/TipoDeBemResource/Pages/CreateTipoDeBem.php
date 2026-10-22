<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\TipoDeBemResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\TipoDeBemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTipoDeBem extends CreateRecord
{
    protected static string $resource = TipoDeBemResource::class;

    protected ?string $heading = 'Adicionar Tipo de bem';
}
