<?php

namespace App\Filament\Egap\Interno\Groups\Agendamento\Resources\EquipeResource\Pages;

use App\Filament\Egap\Interno\Groups\Agendamento\Resources\EquipeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEquipe extends EditRecord
{
    protected static string $resource = EquipeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
