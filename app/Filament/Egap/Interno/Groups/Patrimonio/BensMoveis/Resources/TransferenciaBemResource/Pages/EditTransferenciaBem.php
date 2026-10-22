<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\TransferenciaBemResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\TransferenciaBemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTransferenciaBem extends EditRecord
{
    protected static string $resource = TransferenciaBemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
