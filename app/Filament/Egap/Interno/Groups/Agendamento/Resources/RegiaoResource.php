<?php

namespace App\Filament\Egap\Interno\Groups\Agendamento\Resources;

use App\Filament\Egap\Interno\Groups\Agendamento\Resources\RegiaoResource\Pages\ListRegiaos;
use App\Filament\Egap\Interno\Permissions\Agendamento\AgendamentoResource;
use Filament\Tables\Grouping\Group;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Agendamento\Regiao;
use App\Models\egap\Cadastro\Setores;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class RegiaoResource extends AgendamentoResource
{
    protected static ?string $model = Regiao::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-map';
    protected static string | \UnitEnum | null $navigationGroup = 'Agendamento';
    protected static ?string $modelLabel = 'Região';
    protected static ?string $pluralModelLabel = 'Regiões';
    protected static ?string $navigationLabel = 'Regiões';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(12)
                ->columnSpanFull()
                ->schema([
                    Select::make('regiao')
                        ->label('Região')
                        ->options([
                            '1' => 'Região 1',
                            '2' => 'Região 2',
                            '3' => 'Região 3',
                            '4' => 'Região 4',
                            '5' => 'Região 5',
                            '6' => 'Região 6',
                            '7' => 'Região 7',
                            '8' => 'Região 8',
                            '9' => 'Região 9',
                            '10' => 'Região 10',
                        ])
                        ->required()
                        ->placeholder('Selecione a região atendida')
                        ->columnSpan(12),

                    TextInput::make('sigla')
                        ->label('Sigla')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Ex: GV, SUL, NORTE')
                        ->columnSpan(4),

                    Select::make('unidade')
                        ->label('Unidade Judiciária')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->options(
                            fn() => Setores::query()
                                ->whereColumn('id', 'CodigodaUO')
                                ->orderBy('unidadeorganizacional')
                                ->pluck('unidadeorganizacional', 'codigopai')
                                ->toArray()
                        )
                        ->placeholder('Selecione a unidade')
                        ->columnSpan(8),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('unidadeRef.unidadeorganizacional', 'Unidade', true),

                TableColumns::text('sigla', 'Sigla')
                    ->badge()
                    ->color('info'),

                TableColumns::updatedBy('atualizadoPorRef.name'),
            ])
            ->groups([
                Group::make('regiao')
                    ->label('Região')
                    ->getTitleFromRecordUsing(fn (Regiao $record): string => "Região {$record->regiao}")
                    ->titlePrefixedWithLabel(false)
                    ->orderQueryUsing(fn ($query, string $direction) => $query->orderByRaw('CAST(regiao AS UNSIGNED) ' . $direction))
                    ->collapsible(),
            ])
            ->groupingSettingsHidden()
            ->groupingDirectionSettingHidden()
            ->defaultGroup('regiao')
            ->defaultSort('sigla');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRegiaos::route('/'),
        ];
    }
}
