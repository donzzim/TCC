<?php

namespace App\Filament\Resources\Portal\CMSMenusResource\Pages;

use App\Filament\Resources\Portal\CMSMenusResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCMSMenuses extends ListRecords
{
    protected static string $resource = CMSMenusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
