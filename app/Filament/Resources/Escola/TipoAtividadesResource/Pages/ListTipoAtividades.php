<?php

namespace App\Filament\Resources\Escola\TipoAtividadesResource\Pages;

use App\Filament\Resources\Escola\TipoAtividadesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTipoAtividades extends ListRecords
{
    protected static string $resource = TipoAtividadesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
