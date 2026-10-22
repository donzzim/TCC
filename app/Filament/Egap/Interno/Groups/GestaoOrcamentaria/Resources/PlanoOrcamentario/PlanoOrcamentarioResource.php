<?php

namespace App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\PlanoOrcamentario;

use App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\PlanoOrcamentario\Pages\ListPlanosOrcamentarios;
use App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\PlanoOrcamentario\Schemas\PlanoOrcamentarioForm;
use App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\PlanoOrcamentario\Tables\PlanoOrcamentarioTable;
use App\Models\egap\GestaoOrcamentaria\PlanoOrcamentario;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PlanoOrcamentarioResource extends Resource
{
    protected static ?string $model = PlanoOrcamentario::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function form(Schema $schema): Schema
    {
        return PlanoOrcamentarioForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlanoOrcamentarioTable::configure($table);
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
            'index' => ListPlanosOrcamentarios::route('/'),
        ];
    }
}
