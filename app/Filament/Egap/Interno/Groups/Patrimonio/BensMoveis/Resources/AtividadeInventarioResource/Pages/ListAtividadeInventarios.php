<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\AtividadeInventarioResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\AtividadeInventarioResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAtividadeInventarios extends ListRecords
{
    protected static string $resource = AtividadeInventarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novo')
        ];
    }
}
