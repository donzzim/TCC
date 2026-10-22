<?php

namespace App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Eventos\Schemas;

use App\Filament\Egap\Support\MoneyInput;
use App\Models\egap\GestaoOrcamentaria\Planejamento;
use App\Models\egap\GestaoOrcamentaria\Reserva;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class EventosForm
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
                                Select::make('orcamento')
                                    ->label('Orçamento')
                                    ->relationship('orcamentoRef', 'ano')
                                    ->getOptionLabelFromRecordUsing(
                                        fn(Planejamento $record): string => "{$record->projetoRef?->descricao} - {$record->elementoDespesaRef?->DescricaodaClasse} ({$record->ano})"
                                    )
                                    ->searchable()
                                    ->preload(),

                                Select::make('processo_no')
                                    ->label('Processo')
                                    ->relationship('processoRef', 'num_processo')
                                    ->searchable()
                                    ->preload(),

                                Select::make('no_reserva')
                                    ->label('Reserva')
                                    ->relationship('reservaRef', 'no_reserva')
                                    ->getOptionLabelFromRecordUsing(fn(Reserva $record): string => $record->no_reserva ?: "Reserva #{$record->id}")
                                    ->searchable()
                                    ->preload(),

                                TextInput::make('tipo_evento')
                                    ->label('Tipo de Evento')
                                    ->maxLength(255),
                            ])
                            ->columns(2),

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
