<?php

namespace App\Filament\Resources\Escola\TipoRecursoResource\Pages;

use App\Filament\Resources\Escola\TipoRecursoResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTipoRecurso extends CreateRecord
{
    protected static string $resource = TipoRecursoResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
