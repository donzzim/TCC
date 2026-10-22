<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\TermoResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\TermoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTermos extends ListRecords
{
    protected static string $resource = TermoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novo')
        ];
    }
}
