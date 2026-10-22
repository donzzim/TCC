<?php

namespace App\Filament\Resources\Administrativo\CardResource\Pages;

use App\Filament\Resources\Administrativo\CardResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCard extends CreateRecord
{
    protected static string $resource = CardResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
