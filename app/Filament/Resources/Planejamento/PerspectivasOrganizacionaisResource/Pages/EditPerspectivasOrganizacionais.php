<?php

namespace App\Filament\Resources\Planejamento\PerspectivasOrganizacionaisResource\Pages;

use App\Filament\Resources\Planejamento\PerspectivasOrganizacionaisResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPerspectivasOrganizacionais extends EditRecord
{
    protected static string $resource = PerspectivasOrganizacionaisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
