<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\TipoTributoResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\TipoTributoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTipoTributo extends EditRecord
{
    protected static string $resource = TipoTributoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Excluir'),
        ];
    }
}
