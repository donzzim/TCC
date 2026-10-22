<?php

namespace App\Filament\Egap\Interno\Groups\Cadastro\Resources\SetoresResource\Pages;

use App\Filament\Egap\Interno\Groups\Cadastro\Resources\SetoresResource;
use App\Filament\Egap\Interno\Groups\Cadastro\Resources\SetoresResource\Widgets\SetoresCountStats;
use App\Filament\Egap\Support\CreateActionModal;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListSetores extends ListRecords
{
    protected static string $resource = SetoresResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateActionModal::createAction()
                ->modalWidth(Width::SixExtraLarge)
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SetoresCountStats::class,
        ];
    }
}
