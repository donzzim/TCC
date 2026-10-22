<?php

namespace App\Filament\Resources\Contratacao\OrcamentoResource\Pages;

use App\Filament\Resources\Contratacao\OrcamentoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrcamentos extends ListRecords
{
    protected static string $resource = OrcamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
