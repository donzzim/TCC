<?php

namespace App\Filament\Resources\Administrativo\ListResource\Pages;

use App\Filament\Resources\Administrativo\ListResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditList extends EditRecord
{
    protected static string $resource = ListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
