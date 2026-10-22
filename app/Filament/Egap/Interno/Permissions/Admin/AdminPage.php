<?php

namespace App\Filament\Egap\Interno\Permissions\Admin;

use App\Models\egap\User;
use Filament\Pages\Page;

abstract class AdminPage extends Page
{
    public static function canAccess(): bool
    {
        return AdminAccess::allowed();
    }
}
