<?php

namespace App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Eventos;

use App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Eventos\Pages\ListEventos;
use App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Eventos\Schemas\EventosForm;
use App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Eventos\Tables\EventosTable;
use App\Models\egap\GestaoOrcamentaria\Eventos;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EventosResource extends Resource
{
    protected static ?string $model = Eventos::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    public static function form(Schema $schema): Schema
    {
        return EventosForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EventosTable::configure($table);
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
            'index' => ListEventos::route('/'),
        ];
    }
}
