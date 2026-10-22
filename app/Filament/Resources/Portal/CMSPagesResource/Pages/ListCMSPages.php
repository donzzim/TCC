<?php

namespace App\Filament\Resources\Portal\CMSPagesResource\Pages;

use App\Filament\Resources\Portal\CMSPagesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCMSPages extends ListRecords
{
    protected static string $resource = CMSPagesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
