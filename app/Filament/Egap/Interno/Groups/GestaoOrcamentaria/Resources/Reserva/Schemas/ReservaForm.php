<?php

namespace App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Reserva\Schemas;

use App\Filament\Egap\Support\MoneyInput;
use App\Models\egap\GestaoOrcamentaria\Planejamento;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ReservaForm
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
                                TextInput::make('no_reserva')
                                    ->label('Nº Reserva')
                                    ->maxLength(255),

                                Select::make('processo_no')
                                    ->label('Processo')
                                    ->relationship('processoRef', 'num_processo')
                                    ->searchable()
                                    ->preload(),

                                Select::make('orcamento')
                                    ->label('Orçamento')
                                    ->relationship('orcamentoRef', 'ano')
                                    ->getOptionLabelFromRecordUsing(
                                        fn(Planejamento $record): string => "{$record->projetoRef?->descricao} - {$record->elementoDespesaRef?->DescricaodaClasse} ({$record->ano})"
                                    )
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
                                    ->preload()
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        Tab::make('Gestores e Datas')
                            ->icon(Heroicon::OutlinedUsers)
                            ->schema([
                                Section::make('Gestores')
                                    ->schema([
                                        Select::make('gestor_titular')
                                            ->label('Gestor Titular')
                                            ->relationship('gestorTitularRef', 'name')
                                            ->searchable()
                                            ->preload(),

                                        Select::make('gestor_substituto')
                                            ->label('Gestor Substituto')
                                            ->relationship('gestorSubstitutoRef', 'name')
                                            ->searchable()
                                            ->preload(),
                                    ])
                                    ->columns(2),

                                Section::make('Datas')
                                    ->schema([
                                        DatePicker::make('data_reserva')
                                            ->label('Data da Reserva')
                                            ->displayFormat('d/m/Y')
                                            ->native(false),

                                        DatePicker::make('data_cancelamento')
                                            ->label('Data de Cancelamento')
                                            ->displayFormat('d/m/Y')
                                            ->native(false),
                                    ])
                                    ->columns(2),
                            ]),

                        Tab::make('Valores')
                            ->icon(Heroicon::OutlinedBanknotes)
                            ->schema([
                                MoneyInput::make('valor_1instancia')
                                    ->label('Valor 1ª Instância'),

                                MoneyInput::make('valor_2instancia')
                                    ->label('Valor 2ª Instância'),

                                MoneyInput::make('valor_total')
                                    ->label('Valor Total'),
                            ])
                            ->columns(3),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
