<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\InventarioUnidadeResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\InventarioUnidadeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInventarioUnidade extends EditRecord
{
    protected static string $resource = InventarioUnidadeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
