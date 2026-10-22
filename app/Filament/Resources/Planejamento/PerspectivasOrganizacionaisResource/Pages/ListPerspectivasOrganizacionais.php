<?php

namespace App\Filament\Resources\Planejamento\PerspectivasOrganizacionaisResource\Pages;

use App\Filament\Resources\Planejamento\PerspectivasOrganizacionaisResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPerspectivasOrganizacionais extends ListRecords
{
    protected static string $resource = PerspectivasOrganizacionaisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
