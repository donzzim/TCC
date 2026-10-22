<?php

namespace App\Filament\Resources\Escola\FormResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use App\Filament\Resources\Escola\FormResource;

class ListForms extends ListRecords
{
    protected static string $resource = FormResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
