<?php

namespace App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Reserva;

use App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Reserva\Pages\ListReservas;
use App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Reserva\Schemas\ReservaForm;
use App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Reserva\Tables\ReservaTable;
use App\Models\egap\GestaoOrcamentaria\Reserva;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReservaResource extends Resource
{
    protected static ?string $model = Reserva::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookmark;

    public static function form(Schema $schema): Schema
    {
        return ReservaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReservaTable::configure($table);
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
            'index' => ListReservas::route('/'),
        ];
    }
}
