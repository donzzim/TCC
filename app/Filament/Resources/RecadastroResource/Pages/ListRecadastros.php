<?php

namespace App\Filament\Resources\RecadastroResource\Pages;

use App\Filament\Resources\RecadastroResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRecadastros extends ListRecords
{
    protected static string $resource = RecadastroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
