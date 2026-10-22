<?php

namespace App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Planejamento;

use App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Planejamento\Pages\ListPlanejamentos;
use App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Planejamento\Schemas\PlanejamentoForm;
use App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Planejamento\Tables\PlanejamentoTable;
use App\Models\egap\GestaoOrcamentaria\Planejamento;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PlanejamentoResource extends Resource
{
    protected static ?string $model = Planejamento::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    public static function form(Schema $schema): Schema
    {
        return PlanejamentoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlanejamentoTable::configure($table);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlanejamentos::route('/'),
        ];
    }
}
