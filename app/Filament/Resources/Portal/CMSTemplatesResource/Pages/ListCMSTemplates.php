<?php

namespace App\Filament\Resources\Portal\CMSTemplatesResource\Pages;

use App\Filament\Resources\Portal\CMSTemplatesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCMSTemplates extends ListRecords
{
    protected static string $resource = CMSTemplatesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
