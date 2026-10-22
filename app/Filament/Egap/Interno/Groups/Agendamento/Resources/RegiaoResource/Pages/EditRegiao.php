<?php

namespace App\Filament\Egap\Interno\Groups\Agendamento\Resources\RegiaoResource\Pages;

use App\Filament\Egap\Interno\Groups\Agendamento\Resources\RegiaoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRegiao extends EditRecord
{
    protected static string $resource = RegiaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
