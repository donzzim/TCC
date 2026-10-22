<?php

namespace App\Filament\Resources\Portal\CMSPostsResource\Pages;

use App\Filament\Resources\Portal\CMSPostsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCMSPosts extends ListRecords
{
    protected static string $resource = CMSPostsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
