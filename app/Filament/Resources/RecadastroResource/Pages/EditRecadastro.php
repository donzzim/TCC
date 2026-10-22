<?php

namespace App\Filament\Resources\RecadastroResource\Pages;

use App\Filament\Resources\RecadastroResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRecadastro extends EditRecord
{
    protected static string $resource = RecadastroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
