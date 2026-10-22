<?php

namespace App\Filament\Egap\Interno\Groups\Agendamento\Resources;

use App\Filament\Egap\Interno\Groups\Agendamento\Resources\FrotaResource\Pages\ListFrotas;
use App\Filament\Egap\Interno\Permissions\Agendamento\AgendamentoResource;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Agendamento\Frota;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;

class FrotaResource extends AgendamentoResource
{
    protected static ?string $model = Frota::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-truck';
    protected static string | \UnitEnum | null $navigationGroup = 'Agendamento';
    protected static ?string $modelLabel = 'Veículo';
    protected static ?string $pluralModelLabel = 'Frota';
    protected static ?string $navigationLabel = 'Frota de Veículos';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(12)
                ->schema([
                    TextInput::make('descricao')
                        ->label('Descrição')
                        ->placeholder('Ex: Fiat Strada branca')
                        ->required()
                        ->maxLength(50)
                        ->columnSpan(12),

                    Select::make('marca')
                        ->label('Marca')
                        ->relationship('marcaRef', 'descricao')
                        ->searchable()
                        ->required()
                        ->preload()
                        ->placeholder('Selecione a marca')
                        ->columnSpan(4),

                    Select::make('modelo')
                        ->label('Modelo')
                        ->required()
                        ->relationship('modeloRef', 'descricao')
                        ->searchable()
                        ->preload()
                        ->placeholder('Selecione o modelo')
                        ->columnSpan(4),

                    TextInput::make('placa')
                        ->label('Placa')
                        ->required()
                        ->placeholder('ABC-1D23')
                        ->maxLength(255)
                        ->columnSpan(4),

                    TextInput::make('proprietario')
                        ->label('Proprietário')
                        ->required()
                        ->columnSpan(12),

                    Fieldset::make('Status do veículo')
                        ->schema([
                            Toggle::make('disponivel')
                                ->label('Disponível')
                                ->required()
                                ->default(true)
                                ->inline(false),

                            Toggle::make('ativo')
                                ->label('Ativo')
                                ->required()
                                ->default(true)
                                ->inline(false),
                        ])
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('descricao', 'Descrição', true)
                    ->badge(),

                TableColumns::text('marcaRef.descricao', 'Marca'),

                TableColumns::text('modeloRef.descricao', 'Modelo'),

                TableColumns::text('placa', 'Placa')
                    ->badge()
                    ->color('gray'),

                TableColumns::text('proprietario', 'Proprietário'),

                IconColumn::make('disponivel')
                    ->label('Disponível')
                    ->alignCenter()
                    ->default(false)
                    ->boolean(),

                IconColumn::make('ativo')
                    ->label('Ativo')
                    ->alignCenter()
                    ->boolean(),

                TableColumns::updatedBy('idUserRef.name'),
            ])
            ->defaultSort('descricao', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFrotas::route('/'),
        ];
    }
}
