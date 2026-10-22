<?php

namespace App\Filament\Egap\Interno\Groups\Processo\Resources\ProcessosAdmResource\Pages;

use App\Filament\Egap\Interno\Groups\Processo\Resources\ProcessosAdmResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProcessosAdms extends ListRecords
{
    protected static string $resource = ProcessosAdmResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novo')
        ];
    }
}
