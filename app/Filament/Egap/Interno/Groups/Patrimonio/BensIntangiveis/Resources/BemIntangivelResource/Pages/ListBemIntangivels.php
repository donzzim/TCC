<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensIntangiveis\Resources\BemIntangivelResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensIntangiveis\Resources\BemIntangivelResource;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensIntangiveis\Resources\BemIntangivelResource\Widgets\BensIntangiveisCountStats;
use App\Filament\Egap\Support\CreateActionModal;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListBemIntangivels extends ListRecords
{
    protected static string $resource = BemIntangivelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateActionModal::createAction()
                ->modalWidth(Width::SevenExtraLarge)
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            BensIntangiveisCountStats::class,
        ];
    }
}
