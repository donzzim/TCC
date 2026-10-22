<?php

namespace App\Filament\Resources\Escola\BancoFormadoresResource\Pages;

use App\Filament\Resources\Escola\BancoFormadoresResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBancoFormadores extends EditRecord
{
    protected static string $resource = BancoFormadoresResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
