<?php

namespace App\Filament\Resources\Administrativo\TaskResource\Pages;

use App\Filament\Resources\Administrativo\TaskResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
