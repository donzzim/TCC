<?php

namespace App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Projeto;

use App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Projeto\Pages\ListProjetos;
use App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Projeto\Schemas\ProjetoForm;
use App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Projeto\Tables\ProjetoTable;
use App\Models\egap\GestaoOrcamentaria\Projeto;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProjetoResource extends Resource
{
    protected static ?string $model = Projeto::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    public static function form(Schema $schema): Schema
    {
        return ProjetoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProjetoTable::configure($table);
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
            'index' => ListProjetos::route('/'),
        ];
    }
}
