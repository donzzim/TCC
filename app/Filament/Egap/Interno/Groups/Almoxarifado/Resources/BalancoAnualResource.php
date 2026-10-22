<?php

namespace App\Filament\Egap\Interno\Groups\Almoxarifado\Resources;

use App\Filament\Egap\Interno\Clusters\AlmoxarifadoCluster;
use App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\BalancoAnuals\Pages\ListBalancoAnuals;
use App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\BalancoAnuals\Schemas\BalancoAnualForm;
use App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\BalancoAnuals\Tables\BalancoAnualsTable;
use App\Filament\Egap\Interno\Permissions\Almoxarifado\AlmoxarifadoResource;
use App\Models\Egap\Almoxarifado\BalancoAnual;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BalancoAnualResource extends AlmoxarifadoResource
{
    protected static ?string $model = BalancoAnual::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?string $slug = 'balanco-anual';

    protected static ?int $navigationSort = 7;

    protected static ?string $label = 'Balanço Anual';

    protected static ?string $navigationLabel = 'Balanço Anual';
    protected static ?string $pluralLabel = 'Balanços Anuais';

    protected static ?string $pluralModelLabel = 'Balanços Anuais';

    protected static ?string $cluster = AlmoxarifadoCluster::class;


    public static function form(Schema $schema): Schema
    {
        return BalancoAnualForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BalancoAnualsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBalancoAnuals::route('/'),
        ];
    }
}
