<?php

namespace App\Filament\Resources\Escola\AcaoResource\Pages;

use App\Filament\Resources\Escola\AcaoResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAcao extends CreateRecord
{
    protected static string $resource = AcaoResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
