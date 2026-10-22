<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Resources\Permissions\Pages;

use App\Filament\Egap\Interno\Groups\Admin\Resources\Permissions\PermissionsResource;
use App\Filament\Egap\Interno\Groups\Admin\Resources\Permissions\Widgets\PermissionsOverviewStats;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPermissions extends ListRecords
{
    protected static string $resource = PermissionsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PermissionsOverviewStats::class,
        ];
    }
}
