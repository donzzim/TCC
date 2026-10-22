<?php

namespace App\Filament\Egap\Interno\Groups\Processo\Resources\MateriaisResource\Pages;

use App\Filament\Egap\Interno\Groups\Processo\Resources\MateriaisResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMateriais extends ListRecords
{
    protected static string $resource = MateriaisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novo'),
        ];
    }
}
