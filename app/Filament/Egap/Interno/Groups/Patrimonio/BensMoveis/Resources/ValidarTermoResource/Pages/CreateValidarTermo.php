<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\ValidarTermoResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\ValidarTermoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateValidarTermo extends CreateRecord
{
    protected static string $resource = ValidarTermoResource::class;

    protected static ?string $title = 'Anexos do Termo';
}
