<?php

namespace App\Filament\Egap\Interno\Groups\Agendamento\Resources\FrotaResource\Pages;

use App\Filament\Egap\Interno\Groups\Agendamento\Resources\FrotaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFrotas extends ListRecords
{
    protected static string $resource = FrotaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novo')
        ];
    }
}
