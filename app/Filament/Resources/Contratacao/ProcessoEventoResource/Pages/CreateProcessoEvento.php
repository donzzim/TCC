<?php

namespace App\Filament\Resources\Contratacao\ProcessoEventoResource\Pages;

use App\Filament\Resources\Contratacao\ProcessoEventoResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProcessoEvento extends CreateRecord
{
    protected static string $resource = ProcessoEventoResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
