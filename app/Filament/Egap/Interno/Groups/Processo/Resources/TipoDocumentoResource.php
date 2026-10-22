<?php

namespace App\Filament\Egap\Interno\Groups\Processo\Resources;

use App\Filament\Egap\Interno\Groups\Processo\Resources\TipoDocumentoResource\Pages\ListTipoDocumentos;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Processo\MatTipoDocumento;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Filament\Egap\Interno\Permissions\Processo\ProcessoResource;

class TipoDocumentoResource extends ProcessoResource
{
    protected static ?string $model = MatTipoDocumento::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-duplicate';
    protected static string | \UnitEnum | null $navigationGroup = 'Processos';
    protected static ?string $navigationLabel = 'Tipos de Documento';
    protected static ?string $modelLabel = 'Tipo de Documento';
    protected static ?string $pluralModelLabel = 'Tipos de Documento';
    protected static ?string $slug = 'processos/tipos-documento';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('descricao')
                    ->label('Descrição')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
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
            'index' => ListTipoDocumentos::route('/'),
        ];
    }
}
