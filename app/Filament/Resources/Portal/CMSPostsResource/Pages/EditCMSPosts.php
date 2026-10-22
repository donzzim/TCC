<?php

namespace App\Filament\Resources\Portal\CMSPostsResource\Pages;

use App\Filament\Resources\Portal\CMSPostsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCMSPosts extends EditRecord
{
    protected static string $resource = CMSPostsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
