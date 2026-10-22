<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources;

use App\Filament\Egap\Interno\Clusters\PatrimonioCluster;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\TributoResource\Pages\CreateTributo;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\TributoResource\Pages\EditTributo;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\TributoResource\Pages\ListTributos;
use App\Filament\Egap\Support\MoneyInput;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Patrimonio\BensImoveis\Tributo;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
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

class TributoResource extends PatrimonioResource
{
    protected static ?string $cluster = PatrimonioCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $model = Tributo::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Tributos';
    protected static ?string $modelLabel = 'Tributo';
    protected static ?string $pluralModelLabel = 'Tributos';
    protected static string | \UnitEnum | null $navigationGroup = 'Bens Imóveis';
    protected static ?int $navigationSort = 6;
    protected static ?string $slug = 'bens-imoveis/tributos';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->tabs([
                        Tab::make('Tributos')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Section::make('Identificação')
                                    ->description('Vincule o tributo ao imóvel e informe a natureza da cobrança.')
                                    ->icon('heroicon-o-home-modern')
                                    ->schema([
                                        Select::make('Id_imovel')
                                            ->label('Imóvel')
                                            ->relationship('imovelRelacaoref', 'descricao')
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->native(false)
                                            ->placeholder('Selecione o imóvel')
                                            ->columnSpan(7),

                                        Select::make('tipo_tributo')
                                            ->label('Tipo do tributo')
                                            ->relationship('tipoTributoRelacaoref', 'descricao')
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->native(false)
                                            ->placeholder('Selecione o tipo')
                                            ->columnSpan(5),
                                    ])
                                    ->columns(12),

                                Section::make('Valores e vencimento')
                                    ->description('Dados financeiros previstos para o tributo.')
                                    ->icon('heroicon-o-calendar-days')
                                    ->schema([
                                        DatePicker::make('vencimento')
                                            ->label('Vencimento')
                                            ->displayFormat('d/m/Y')
                                            ->native(false)
                                            ->placeholder('dd/mm/aaaa')
                                            ->columnSpan(6),

                                        MoneyInput::make('valor')
                                            ->label('Valor')
                                            ->columnSpan(6),
                                    ])
                                    ->columns(12),

                                Section::make('Pagamento')
                                    ->description('Preencha quando houver registro de quitação.')
                                    ->icon('heroicon-o-credit-card')
                                    ->schema([
                                        DatePicker::make('pago_em')
                                            ->label('Pago em')
                                            ->displayFormat('d/m/Y')
                                            ->native(false)
                                            ->placeholder('dd/mm/aaaa')
                                            ->columnSpan(4),

                                        MoneyInput::make('valor_pago')
                                            ->label('Valor pago')
                                            ->columnSpan(4),

                                        TextInput::make('processo_pagto')
                                            ->label('Processo de pagamento')
                                            ->placeholder('Informe o processo, se houver')
                                            ->columnSpan(4),
                                    ])
                                    ->columns(12),

                                Section::make('Auditoria e observações')
                                    ->description('Controle interno de atualização e informações complementares.')
                                    ->icon('heroicon-o-clipboard-document-list')
                                    ->schema([
                                        Textarea::make('observacao')
                                            ->label('Observação')
                                            ->rows(4)
                                            ->placeholder('Registre informações relevantes sobre o tributo')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(12),
                            ]),

                        Tab::make('Eventos')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                Section::make('Histórico de eventos')
                                    ->description('Registre ocorrências relacionadas ao tributo.')
                                    ->icon('heroicon-o-queue-list')
                                    ->schema([
                                        Repeater::make('eventos')
                                            ->required()
                                            ->schema([
                                                DatePicker::make('data')
                                                    ->label('Data')
                                                    ->displayFormat('d/m/Y')
                                                    ->native(false)
                                                    ->placeholder('dd/mm/aaaa')
                                                    ->columnSpanFull(),

                                                Textarea::make('descricao')
                                                    ->label('Descrição')
                                                    ->rows(2)
                                                    ->placeholder('Descreva o evento')
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(12)
                                            ->defaultItems(1)
                                            ->minItems(1)
                                            ->addActionLabel('Adicionar evento')
                                            ->collapsible()
                                            ->reorderableWithButtons()
                                            ->hiddenLabel(),
                                    ])
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('imovelRelacaoref.descricao', 'Imóvel', isFirstColumn: true),
                TableColumns::text('tipoTributoRelacaoref.descricao', 'Tipo do tributo'),
                TableColumns::date('vencimento', 'Vencimento'),
                TableColumns::money('valor', 'Valor'),
                TableColumns::date('pago_em', 'Pago em'),
                TableColumns::money('valor_pago', 'Valor Pago'),
                TableColumns::text('processo_pagto', 'Processo Pagto')
                    ->badge(),
                TableColumns::text('observacao', 'Observação')
                    ->wrap(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTributos::route('/'),
            'create' => CreateTributo::route('/create'),
            'edit' => EditTributo::route('/{record}/edit'),
        ];
    }
}
