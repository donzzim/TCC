<?php

namespace App\Filament\Resources\Planejamento\MacrodesafiosEstrategicosResource\Pages;

use App\Filament\Resources\Planejamento\MacrodesafiosEstrategicosResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMacrodesafiosEstrategicos extends EditRecord
{
    protected static string $resource = MacrodesafiosEstrategicosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
