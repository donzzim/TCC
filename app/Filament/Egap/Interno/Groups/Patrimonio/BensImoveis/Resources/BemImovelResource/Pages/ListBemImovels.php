<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\BemImovelResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\BemImovelResource;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\BemImovelResource\Widgets\BensImoveisCountStats;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBemImovels extends ListRecords
{
    protected static string $resource = BemImovelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novo')
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            BensImoveisCountStats::class,
        ];
    }
}
