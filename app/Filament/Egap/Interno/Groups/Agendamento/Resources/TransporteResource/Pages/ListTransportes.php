<?php

namespace App\Filament\Egap\Interno\Groups\Agendamento\Resources\TransporteResource\Pages;

use App\Filament\Egap\Interno\Groups\Agendamento\Resources\TransporteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTransportes extends ListRecords
{
    protected static string $resource = TransporteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novo')
        ];
    }
}
