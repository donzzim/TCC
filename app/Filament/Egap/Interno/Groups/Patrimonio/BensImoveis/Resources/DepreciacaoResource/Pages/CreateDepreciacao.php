<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\DepreciacaoResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\DepreciacaoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDepreciacao extends CreateRecord
{
    protected static string $resource = DepreciacaoResource::class;

    protected ?string $heading = 'Adicionar Depreciação';
}
