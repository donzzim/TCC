<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\DepreciacaoResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\DepreciacaoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDepreciacaos extends ListRecords
{
    protected static string $resource = DepreciacaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novo'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return static::getResource()::getWidgets();
    }

    public function getWidgetData(): array
    {
        return [
            'patrimonioId' => $this->tableFilters['patrimonio']['patrimonio'] ?? null,
        ];
    }
}
