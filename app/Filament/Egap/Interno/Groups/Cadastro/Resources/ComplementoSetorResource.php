<?php

namespace App\Filament\Egap\Interno\Groups\Cadastro\Resources;

use App\Filament\Egap\Interno\Groups\Cadastro\Resources\ComplementoSetorResource\Pages\ListComplementoSetors;
use App\Filament\Egap\Resources\Cadastro\ComplementoSetorResource\Pages;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Cadastro\ComplementoSetor;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Filament\Egap\Interno\Permissions\Cadastro\CadastroResource;

class ComplementoSetorResource extends CadastroResource
{
    protected static ?string $model = ComplementoSetor::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Complemento de Setor';

    protected static ?string $modelLabel = 'Complemento de Setor';

    protected static ?string $pluralModelLabel = 'Complementos de Setor';

    protected static string | \UnitEnum | null $navigationGroup = 'Cadastro';

    protected static ?int $navigationSort = 7;

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
                    ->wrap(),
                TableColumns::updatedBy('atualizado_por.name'),
            ])
            ->defaultSort('id', 'asc')
            ->filters([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListComplementoSetors::route('/'),
        ];
    }
}
