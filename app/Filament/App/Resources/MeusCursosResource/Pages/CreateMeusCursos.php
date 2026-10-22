<?php

namespace App\Filament\App\Resources\MeusCursosResource\Pages;

use App\Filament\App\Resources\MeusCursosResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMeusCursos extends CreateRecord
{
    protected static string $resource = MeusCursosResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
