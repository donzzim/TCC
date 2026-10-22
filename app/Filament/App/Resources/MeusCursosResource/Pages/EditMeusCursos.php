<?php

namespace App\Filament\App\Resources\MeusCursosResource\Pages;

use App\Filament\App\Resources\MeusCursosResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMeusCursos extends EditRecord
{
    protected static string $resource = MeusCursosResource::class;

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
