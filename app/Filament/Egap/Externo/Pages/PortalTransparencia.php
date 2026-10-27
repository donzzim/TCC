<?php

namespace App\Filament\Egap\Externo\Pages;

use App\Filament\Egap\Externo\Permissions\ExternoPage;
use App\Filament\Egap\Externo\Clusters\ExternoCluster;
use Filament\Pages\Page;

class PortalTransparencia extends ExternoPage
{
    protected static ?string $navigationLabel = 'Portal Transparência';

    protected static ?int $navigationSort = 100;

    protected static ?string $cluster = ExternoCluster::class;

    protected static ?string $title = 'Portal Transparência';

    protected string $view = 'egap.filament.pages.externo.portal-transparencia';
}
