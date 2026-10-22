<?php

namespace App\Filament\Resources\Escola\FormResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Filament\Actions;
use App\Filament\Resources\Escola\FormResource;

class EditForm extends EditRecord
{
    protected static string $resource = FormResource::class;
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
