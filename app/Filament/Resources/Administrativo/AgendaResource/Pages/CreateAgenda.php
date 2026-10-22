<?php

namespace App\Filament\Resources\Administrativo\AgendaResource\Pages;

use App\Filament\Resources\Administrativo\AgendaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAgenda extends CreateRecord
{
    protected static string $resource = AgendaResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
