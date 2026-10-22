<?php

namespace App\Filament\Egap\Interno\Groups\Processo\Resources\ProcessosAdmResource\Pages;

use App\Filament\Egap\Interno\Groups\Processo\Resources\ProcessosAdmResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProcessosAdm extends EditRecord
{
    protected static string $resource = ProcessosAdmResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
