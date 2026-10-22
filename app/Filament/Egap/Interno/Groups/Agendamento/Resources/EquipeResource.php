<?php

namespace App\Filament\Egap\Interno\Groups\Agendamento\Resources;

use App\Filament\Egap\Interno\Groups\Agendamento\Resources\EquipeResource\Pages\ListEquipes;
use App\Filament\Egap\Interno\Permissions\Agendamento\AgendamentoResource;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Agendamento\Equipe;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;

class EquipeResource extends AgendamentoResource
{
    protected static ?string $model = Equipe::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';
    protected static string | \UnitEnum | null $navigationGroup = 'Agendamento';
    protected static ?string $modelLabel = 'Equipe de Transporte';
    protected static ?string $pluralModelLabel = 'Equipes de Transporte';
    protected static ?string $navigationLabel = 'Equipe de Transporte';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(12)
                ->columnSpanFull()
                ->schema([
                    Select::make('id_pessoa')
                        ->label('Pessoa')
                        ->relationship('idPessoaRef', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->placeholder('Selecione uma pessoa')
                        ->native(false)
                        ->columnSpan(6),

                    Select::make('funcao')
                        ->label('Função')
                        ->options([
                            'Condutor' => 'Condutor',
                            'Carregador' => 'Carregador',
                            'Controlador' => 'Controlador',
                        ])
                        ->required()
                        ->columnSpan(6),

                    TextInput::make('contato')
                        ->label('Contato')
                        ->required()
                        ->numeric()
                        ->mask('(**)*****-****')
                        ->maxLength(255)
                        ->placeholder('Telefone, ramal ou outro contato')
                        ->columnSpan(12),

                    Fieldset::make('Status')
                        ->schema([
                            Toggle::make('disponivel')
                                ->label('Disponível')
                                ->default(true)
                                ->inline(false),

                            Toggle::make('ativo')
                                ->label('Ativo')
                                ->default(true)
                                ->inline(false),
                        ])
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('funcao', 'Função', true)
                    ->badge()
                    ->color('gray'),

                TableColumns::text('idPessoaRef.name', 'Nome')
                    ->weight('medium')
                    ->wrap(),

                TableColumns::text('contato', 'Contato')
                    ->copyable()
                    ->wrap(),

                IconColumn::make('disponivel')
                    ->label('Disponível')
                    ->boolean()
                    ->alignCenter(),

                IconColumn::make('ativo')
                    ->label('Ativo')
                    ->boolean()
                    ->alignCenter(),

                TableColumns::updatedBy('idUserRef.name'),
            ])
            ->defaultSort('funcao', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEquipes::route('/'),
        ];
    }
}
