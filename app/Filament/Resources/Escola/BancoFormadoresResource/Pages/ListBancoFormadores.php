<?php

namespace App\Filament\Resources\Escola\BancoFormadoresResource\Pages;

use App\Filament\Resources\Escola\BancoFormadoresResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBancoFormadores extends ListRecords
{
    protected static string $resource = BancoFormadoresResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
