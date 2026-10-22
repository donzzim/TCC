<?php

namespace App\Filament\Egap\Interno\Groups\Agendamento\Resources\FrotaResource\Pages;

use App\Filament\Egap\Interno\Groups\Agendamento\Resources\FrotaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFrota extends EditRecord
{
    protected static string $resource = FrotaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
