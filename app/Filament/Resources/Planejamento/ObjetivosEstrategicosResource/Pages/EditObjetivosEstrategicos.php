<?php

namespace App\Filament\Resources\Planejamento\ObjetivosEstrategicosResource\Pages;

use App\Filament\Resources\Planejamento\ObjetivosEstrategicosResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditObjetivosEstrategicos extends EditRecord
{
    protected static string $resource = ObjetivosEstrategicosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
