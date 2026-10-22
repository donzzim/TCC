<?php

namespace App\Filament\Egap\Pages\Widgets\EgapDashboard;

use Filament\Widgets\Widget;

class AccountWidget extends Widget
{
    protected static ?int $sort = -3;

    protected static bool $isLazy = false;

    protected string $view = 'egap.filament.widgets.account-widget';

    protected int | string | array $columnSpan = 'full';
}
