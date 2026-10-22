<?php

namespace App\Filament\Egap\Interno\Groups\Agendamento\Resources\TransporteResource\Pages;

use App\Filament\Egap\Interno\Groups\Agendamento\Resources\TransporteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTransporte extends EditRecord
{
    protected static string $resource = TransporteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
