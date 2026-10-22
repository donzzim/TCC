<?php

namespace App\Filament\Resources\Administrativo\RegistroFuncionalResource\Pages;

use App\Filament\Resources\Administrativo\RegistroFuncionalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRegistroFuncional extends EditRecord
{
    protected static string $resource = RegistroFuncionalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
