<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Resources\Roles\Pages;

use App\Filament\Egap\Interno\Groups\Admin\Resources\Roles\RolesResource;
use App\Filament\Egap\Interno\Groups\Admin\Resources\Roles\Widgets\RolesOverviewStats;
use App\Filament\Egap\Support\CreateActionModal;
use Filament\Resources\Pages\ListRecords;

class ListRoles extends ListRecords
{
    protected static string $resource = RolesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateActionModal::createAction()
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RolesOverviewStats::class,
        ];
    }
}
