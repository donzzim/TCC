<?php

namespace App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Acao;

use App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Acao\Pages\ListAcoes;
use App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Acao\Schemas\AcaoForm;
use App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Acao\Tables\AcaoTable;
use App\Models\egap\GestaoOrcamentaria\Acao;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AcaoResource extends Resource
{
    protected static ?string $model = Acao::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    public static function form(Schema $schema): Schema
    {
        return AcaoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AcaoTable::configure($table);
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
            'index' => ListAcoes::route('/'),
        ];
    }
}
