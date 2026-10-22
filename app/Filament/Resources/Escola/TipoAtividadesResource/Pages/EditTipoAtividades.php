<?php

namespace App\Filament\Resources\Escola\TipoAtividadesResource\Pages;

use App\Filament\Resources\Escola\TipoAtividadesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTipoAtividades extends EditRecord
{
    protected static string $resource = TipoAtividadesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
