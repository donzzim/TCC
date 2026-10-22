<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources;

use App\Filament\Egap\Interno\Clusters\PatrimonioCluster;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\ReavaliacaoResource\Pages\CreateReavaliacao;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\ReavaliacaoResource\Pages\EditReavaliacao;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\ReavaliacaoResource\Pages\ListReavaliacoes;
use App\Filament\Egap\Resources\Patrimonio\BensImoveis\ReavaliacaoResource\Pages;
use App\Filament\Egap\Support\MoneyInput;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Patrimonio\BensImoveis\Reavaliacao;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Egap\Interno\Permissions\Patrimonio\PatrimonioResource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ReavaliacaoResource extends PatrimonioResource
{
    protected static ?string $cluster = PatrimonioCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $model = Reavaliacao::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Reavaliação';
    protected static ?string $modelLabel = 'Reavaliação';
    protected static ?string $pluralModelLabel = 'Reavaliação';
    protected static string | \UnitEnum | null $navigationGroup = 'Bens Imóveis';
    protected static ?int $navigationSort = 3;
    protected static ?string $slug = 'bens-imoveis/reavaliacoes';

    public static function form(Schema $schema): Schema
    {
        $monthsInput = fn(string $field, string $label) => TextInput::make($field)
            ->label($label)
            ->numeric()
            ->suffix('meses')
            ->placeholder('0');

        $yearsInput = fn(string $field, string $label) => TextInput::make($field)
            ->label($label)
            ->numeric()
            ->suffix('anos')
            ->placeholder('0');

        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->tabs([
                        Tab::make('Reavaliação')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Section::make('Identificação do Imóvel')
                                    ->icon('heroicon-o-building-office')
                                    ->schema([
                                        Select::make('Id_imovel')
                                            ->label('Imóvel')
                                            ->required()
                                            ->relationship('imovelRelacaoref', 'descricao')
                                            ->searchable()
                                            ->preload()
                                            ->placeholder('Selecione o imóvel')
                                            ->columnSpan(8),

                                        Select::make('Id_estadoconservacao')
                                            ->label('Estado de Conservação')
                                            ->required()
                                            ->relationship('estadoConservacaoRelacaoref', 'descEstadoConservacao')
                                            ->searchable()
                                            ->preload()
                                            ->placeholder('Selecione o estado de conservação')
                                            ->columnSpan(4),
                                    ])
                                    ->columns(12),

                                Section::make('Dados da Reavaliação')
                                    ->icon('heroicon-o-calculator')
                                    ->schema([
                                        DatePicker::make('data_reavaliacao')
                                            ->label('Data da Reavaliação')
                                            ->default(now())
                                            ->displayFormat('d/m/Y')
                                            ->native(false),

                                        MoneyInput::make('valor_reavaliacao')
                                            ->label('Valor da Reavaliação'),

                                        TextInput::make('vida_util_reavaliacao')
                                            ->label('Vida Útil da Reavaliação')
                                            ->numeric()
                                            ->suffix('meses')
                                            ->placeholder('0'),

                                        MoneyInput::make('ajuste_contabil')
                                            ->label('Ajuste Contábil'),

                                        Textarea::make('observacao')
                                            ->label('Observação')
                                            ->placeholder('Registre informações relevantes sobre a reavaliação')
                                            ->columnSpanFull()
                                            ->rows(4),
                                    ])
                                    ->columns(2),
                            ]),

                        Tab::make('Complemento')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->schema([
                                Section::make('Valores de Referência')
                                    ->icon('heroicon-o-banknotes')
                                    ->schema([
                                        MoneyInput::make('valor_mercado')
                                            ->label('Valor de Mercado'),
                                        MoneyInput::make('valor_aquisicao')
                                            ->label('Valor de Aquisição'),
                                    ])
                                    ->columns(2),

                                Section::make('Prazos e Vida Útil')
                                    ->icon('heroicon-o-calendar-days')
                                    ->schema([
                                        $monthsInput('vida_util_siafi', 'Vida Útil SIAFI'),
                                        $monthsInput('vida_util', 'Vida Útil'),
                                        $monthsInput('tempo_utilizacao_meses', 'Tempo de Utilização'),
                                        $monthsInput('vida_util_remanescente_meses', 'Vida Útil Remanescente'),
                                        $yearsInput('vida_util_estimada_anos', 'Vida Útil Estimada'),
                                        $yearsInput('utilizacao_bem_anos', 'Utilização do Bem'),
                                        $yearsInput('idade_aparente_anos', 'Idade Aparente'),
                                    ])
                                    ->columns(3),

                                Section::make('Parâmetros Técnicos')
                                    ->icon('heroicon-o-adjustments-horizontal')
                                    ->schema([
                                        TextInput::make('PUB1')
                                            ->label('PUB1')
                                            ->numeric()
                                            ->placeholder('0'),

                                        TextInput::make('PUV')
                                            ->label('PUV')
                                            ->numeric()
                                            ->placeholder('0'),

                                        TextInput::make('FR')
                                            ->label('FR')
                                            ->numeric()
                                            ->placeholder('0'),
                                    ])
                                    ->columns(3),

                                Section::make('Controle')
                                    ->icon('heroicon-o-clipboard-document-list')
                                    ->schema([
                                        DateTimePicker::make('data_disponibilizacao')
                                            ->label('Data de Disponibilização')
                                            ->default(now())
                                            ->columnSpan(6)
                                            ->displayFormat('d/m/Y H:i:s')
                                            ->native(false),

                                        DateTimePicker::make('data_referencia')
                                            ->label('Data de Referência')
                                            ->default(now())
                                            ->columnSpan(6)
                                            ->displayFormat('d/m/Y H:i:s')
                                            ->native(false),
                                    ])
                                    ->columns(12),
                            ]),
                    ])
                    ->columnSpanFull()
            ]);
    }
    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('Id', '#', isFirstColumn: true, isIdColumn: true),
                TableColumns::text('imovelRelacaoref.descricao', 'Imóvel')
                    ->limit(45)
                    ->tooltip(fn($record): ?string => $record->imovelRelacaoref?->descricao),
                TableColumns::date('data_reavaliacao', 'Data da Reavaliação'),
                TableColumns::money('valor_reavaliacao', 'Valor da Reavaliação'),
                TableColumns::text('vida_util_reavaliacao', 'Vida Útil')
                    ->badge()
                    ->suffix(' meses'),
                TableColumns::text('estadoConservacaoRelacaoref.descEstadoConservacao', 'Estado de Conservação'),
                TableColumns::money('ajuste_contabil', 'Ajuste Contábil'),
                TableColumns::text('observacao', 'Observação')
                    ->limit(50)
                    ->tooltip(fn($record): ?string => $record->observacao),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReavaliacoes::route('/'),
            'create' => CreateReavaliacao::route('/create'),
            'edit' => EditReavaliacao::route('/{record}/edit'),
        ];
    }
}
