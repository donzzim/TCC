<?php

namespace App\Filament\Egap\Interno\Groups\Processo\Resources\AnexosProcessos;

use App\Filament\Egap\Interno\Groups\Processo\Resources\AnexosProcessos\Pages\ListAnexosProcessos;
use App\Filament\Egap\Interno\Groups\Processo\Resources\AnexosProcessos\Schemas\AnexosProcessoForm;
use App\Filament\Egap\Interno\Groups\Processo\Resources\AnexosProcessos\Tables\AnexosProcessosTable;
use App\Models\egap\Processo\MatAnexoProcesso;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Filament\Egap\Interno\Permissions\Processo\ProcessoResource;

class AnexosProcessoResource extends ProcessoResource
{
    protected static ?string $model = MatAnexoProcesso::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return AnexosProcessoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AnexosProcessosTable::configure($table);
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
            'index' => ListAnexosProcessos::route('/'),
        ];
    }
}
