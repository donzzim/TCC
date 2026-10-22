<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensIntangiveis\Resources\TipoBemIntangivelResource\Pages;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensIntangiveis\Resources\TipoBemIntangivelResource;
use App\Filament\Egap\Support\CreateActionModal;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTipoBemIntangivels extends ListRecords
{
    protected static string $resource = TipoBemIntangivelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateActionModal::createAction()
        ];
    }
}
