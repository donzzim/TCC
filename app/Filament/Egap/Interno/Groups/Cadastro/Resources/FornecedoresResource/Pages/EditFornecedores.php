<?php

namespace App\Filament\Egap\Interno\Groups\Cadastro\Resources\FornecedoresResource\Pages;

use App\Filament\Egap\Interno\Groups\Cadastro\Resources\FornecedoresResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFornecedores extends EditRecord
{
    protected static string $resource = FornecedoresResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
