<?php

namespace App\Filament\Resources\Administrativo\TarefaResource\Pages;

use App\Filament\Resources\Administrativo\TarefaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTarefas extends ListRecords
{
    protected static string $resource = TarefaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
