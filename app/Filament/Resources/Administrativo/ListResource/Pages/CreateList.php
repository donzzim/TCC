<?php

namespace App\Filament\Resources\Administrativo\ListResource\Pages;

use App\Filament\Resources\Administrativo\ListResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateList extends CreateRecord
{
    protected static string $resource = ListResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
