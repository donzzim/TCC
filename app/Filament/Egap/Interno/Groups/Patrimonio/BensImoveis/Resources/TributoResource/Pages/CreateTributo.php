<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\TributoResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\TributoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTributo extends CreateRecord
{
    protected static string $resource = TributoResource::class;

    protected ?string $heading = 'Adicionar Tributo';
}
