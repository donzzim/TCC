<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Pages;

use App\Filament\Egap\Interno\Clusters\PatrimonioCluster;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\BemMovelResource;
use App\Filament\Egap\Interno\Permissions\Patrimonio\PatrimonioPage;
use Filament\Pages\Page;

class CadastroManual extends PatrimonioPage
{
    protected static ?string $cluster = PatrimonioCluster::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $navigationLabel = 'Cadastro manual do bem';

    protected static string | \UnitEnum | null $navigationGroup = 'Bens Móveis';

    protected static ?int $navigationSort = 9;

    public function mount(): void
    {
        $this->redirect(BemMovelResource::getUrl('create'));
    }
}
