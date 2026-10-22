<?php

namespace App\Filament\Resources\Planejamento\IniciativasEstrategicasResource\Pages;

use App\Filament\Resources\Planejamento\IniciativasEstrategicasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIniciativasEstrategicas extends EditRecord
{
    protected static string $resource = IniciativasEstrategicasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
