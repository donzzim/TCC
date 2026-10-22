<?php

namespace App\Filament\Egap\Interno\Groups\Processo\Resources;

use App\Filament\Egap\Interno\Groups\Processo\Resources\TipoProcessoResource\Pages\CreateTipoProcesso;
use App\Filament\Egap\Interno\Groups\Processo\Resources\TipoProcessoResource\Pages\EditTipoProcesso;
use App\Filament\Egap\Interno\Groups\Processo\Resources\TipoProcessoResource\Pages\ListTipoProcessos;
use App\Filament\Egap\Resources\Processo\TipoProcessoResource\Pages;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Processo\MatTipoProcesso;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Filament\Egap\Interno\Permissions\Processo\ProcessoResource;

class TipoProcessoResource extends ProcessoResource
{
    protected static ?string $model = MatTipoProcesso::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-tag';
    protected static string | \UnitEnum | null $navigationGroup = 'Processos';
    protected static ?string $navigationLabel = 'Tipos de Processo';
    protected static ?string $modelLabel = 'Tipo de Processo';
    protected static ?string $pluralModelLabel = 'Tipos de Processo';
    protected static ?string $slug = 'processos/tipos-processos';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('descricao')
                    ->required()
                    ->maxLength(255)
                    ->label('Descrição')
                    ->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('id', '#', isFirstColumn: true, isIdColumn: true),
                TableColumns::text('descricao', 'Descrição')
                    ->alignLeft()
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTipoProcessos::route('/'),
        ];
    }
}
