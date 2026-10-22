<?php

namespace App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Empenho;

use App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Empenho\Pages\ListEmpenhos;
use App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Empenho\Schemas\EmpenhoForm;
use App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Empenho\Tables\EmpenhoTable;
use App\Models\egap\GestaoOrcamentaria\Empenho;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EmpenhoResource extends Resource
{
    protected static ?string $model = Empenho::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function form(Schema $schema): Schema
    {
        return EmpenhoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmpenhoTable::configure($table);
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
            'index' => ListEmpenhos::route('/'),
        ];
    }
}
