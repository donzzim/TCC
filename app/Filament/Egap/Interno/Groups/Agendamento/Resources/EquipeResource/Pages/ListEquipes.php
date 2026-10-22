<?php

namespace App\Filament\Egap\Interno\Groups\Agendamento\Resources\EquipeResource\Pages;

use App\Filament\Egap\Interno\Groups\Agendamento\Resources\EquipeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEquipes extends ListRecords
{
    protected static string $resource = EquipeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novo')
        ];
    }
}
