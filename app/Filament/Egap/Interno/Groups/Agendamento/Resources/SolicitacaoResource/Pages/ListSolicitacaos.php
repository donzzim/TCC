<?php

namespace App\Filament\Egap\Interno\Groups\Agendamento\Resources\SolicitacaoResource\Pages;

use App\Filament\Egap\Interno\Groups\Agendamento\Resources\SolicitacaoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListSolicitacaos extends ListRecords
{
    protected static string $resource = SolicitacaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novo')
                ->modalWidth(Width::SevenExtraLarge)
        ];
    }
}
