<?php

namespace App\Filament\Resources\Contratacao\ProcessoResource\Pages;

use App\Filament\Resources\Contratacao\ProcessoResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProcesso extends CreateRecord
{
    protected static string $resource = ProcessoResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
