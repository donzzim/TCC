<?php

namespace App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Planejamento\Schemas;

use App\Filament\Egap\Support\MoneyInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class PlanejamentoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->tabs([
                        Tab::make('Vínculo')
                            ->icon(Heroicon::OutlinedLink)
                            ->schema([
                                Select::make('projeto')
                                    ->label('Projeto')
                                    ->relationship('projetoRef', 'descricao')
                                    ->searchable()
                                    ->preload(),

                                Select::make('elemento_despesa')
                                    ->label('Elemento de Despesa')
                                    ->relationship('elementoDespesaRef', 'DescricaodaClasse')
                                    ->searchable()
                                    ->preload(),

                                Select::make('unidade_requisitante')
                                    ->label('Unidade Requisitante')
                                    ->relationship('unidadeRequisitanteRef', 'Setor')
                                    ->searchable()
                                    ->preload(),

                                TextInput::make('ano')
                                    ->label('Ano')
                                    ->numeric()
                                    ->required(),
                            ])
                            ->columns(2),

                        Tab::make('Valores')
                            ->icon(Heroicon::OutlinedBanknotes)
                            ->schema([
                                Section::make('Valores')
                                    ->schema([
                                        MoneyInput::make('valor_1instancia')
                                            ->label('Valor 1ª Instância'),

                                        MoneyInput::make('valor_2instancia')
                                            ->label('Valor 2ª Instância'),

                                        MoneyInput::make('valor_total')
                                            ->label('Valor Total'),
                                    ])
                                    ->columns(3),

                                Section::make('Saldos')
                                    ->schema([
                                        MoneyInput::make('saldo_1instancia')
                                            ->label('Saldo 1ª Instância'),

                                        MoneyInput::make('saldo_2instancia')
                                            ->label('Saldo 2ª Instância'),

                                        MoneyInput::make('saldo_atual')
                                            ->label('Saldo Atual'),
                                    ])
                                    ->columns(3),

                                Section::make('Previsão')
                                    ->schema([
                                        MoneyInput::make('previsao_1instancia')
                                            ->label('Previsão 1ª Instância'),

                                        MoneyInput::make('previsao_2instancia')
                                            ->label('Previsão 2ª Instância'),

                                        MoneyInput::make('previsao_total')
                                            ->label('Previsão Total'),
                                    ])
                                    ->columns(3),
                            ]),

                        Tab::make('Observações')
                            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                            ->schema([
                                Textarea::make('observacao')
                                    ->label('Observação')
                                    ->rows(6)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
