<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\ObraResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\ObraResource;
use Filament\Resources\Pages\CreateRecord;

class CreateObra extends CreateRecord
{
    protected static string $resource = ObraResource::class;

    protected ?string $heading = 'Adicionar Obras e Ampliações';
}
