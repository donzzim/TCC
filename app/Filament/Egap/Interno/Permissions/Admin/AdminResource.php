<?php

namespace App\Filament\Egap\Interno\Permissions\Admin;

use Filament\Resources\Resource;

abstract class AdminResource extends Resource
{
    public static function canAccess(): bool
    {
        return AdminAccess::allowed();
    }
}
