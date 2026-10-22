<?php

namespace App\Filament\Resources\Escola\CursoCertificadosResource\Pages;

use App\Filament\Resources\Escola\CursoCertificadosResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCursoCertificados extends EditRecord
{
    protected static string $resource = CursoCertificadosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
