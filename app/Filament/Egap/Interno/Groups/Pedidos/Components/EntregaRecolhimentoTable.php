<?php

namespace App\Filament\Egap\Interno\Groups\Pedidos\Components;

use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Filament\Egap\Support\TableModalAction;
use App\Models\egap\Agendamento\Solicitacao;
use App\Models\egap\Patrimonio\BensMoveis\ArquivoDigital;
use App\Models\egap\Patrimonio\BensMoveis\Termo;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Livewire\Livewire;

class EntregaRecolhimentoTable extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    private const SETOR_PATRIMONIO = 1239;

    private const SITUACAO_ENCAMINHADO = 'Encaminhado para Logística';

    public function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->query(self::getQuery())
            ->columns([
                TableColumns::text('entrega_recolhimento', 'Entrega/Recolhimento', true)
                    ->getStateUsing(fn (Termo $record): string => self::tipo($record))
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Entrega' ? 'success' : 'info'),

                TableColumns::text('origem', 'Origem')
                    ->getStateUsing(fn (Termo $record): string => $record->ultimaTransferencia?->setorAnteriorRel?->setor ?? '-')
                    ->description(fn (Termo $record): ?string => $record->ultimaTransferencia?->complementoAnteriorRel?->descricao),

                TableColumns::text('destino', 'Destino')
                    ->getStateUsing(fn (Termo $record): string => $record->ultimaTransferencia?->setorAtualRel?->setor ?? '-')
                    ->description(fn (Termo $record): ?string => $record->ultimaTransferencia?->complementoAtualRel?->descricao),

                TableColumns::updatedBy('ultimaTransferencia.usuarioRef.name')
                    ->description(fn (Termo $record): ?string => $record->ultimaTransferencia?->date_time?->format('d/m/Y H:i')),

                TableColumns::text('arquivoDigital.arquivo_digital', 'Termo')
                    ->url(fn (Termo $record): ?string => $record->arquivoDigital?->url)
                    ->openUrlInNewTab()
                    ->formatStateUsing(fn (Termo $record) => $record->arquivoDigital?->arquivo_digital ? 'Abrir Termo' : '-')
                    ->weight('bold')
                    ->color(fn (Termo $record): string => $record->arquivoDigital?->arquivo_digital ? 'primary' : 'gray')
                    ->tooltip(fn (Termo $record): ?string => $record->arquivoDigital?->arquivo_digital ? 'Clique para visualizar o termo' : null),

                TableColumns::text('arquivoDigital.situacao', 'Situação do Termo')
                    ->formatStateUsing(fn ($state): string => ArquivoDigital::situacaoLabel($state))
                    ->badge()
                    ->color(fn ($state): string => ArquivoDigital::situacaoColor($state)),

                TableColumns::text('arquivoDigital.observacao', 'Observação')
                    ->limit(50)
                    ->tooltip(fn (Termo $record): ?string => $record->arquivoDigital?->observacao),

                TableColumns::text('pedido_no', 'Pedido')
                    ->badge()
                    ->color('gray'),

                TableColumns::text('situacao_solicitacao', 'Situação da Solicitação')
                    ->getStateUsing(fn (Termo $record): string => $record->ultimoMaterialTransporte?->idSolicitacaoRef?->idSituacaoRef?->Descricao ?? 'Não encaminhado')
                    ->badge()
                    ->color(fn (Termo $record): string => match ((int) ($record->ultimoMaterialTransporte?->idSolicitacaoRef?->id_situacao ?? 0)) {
                        Solicitacao::SITUACAO_EM_ANALISE => 'warning',
                        Solicitacao::SITUACAO_AGENDADO => 'info',
                        Solicitacao::SITUACAO_CONCLUIDO => 'success',
                        default => 'gray',
                    }),

                TableColumns::text('situacao_entrega', 'Situação da Entrega')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Entregue' => 'success',
                        'Em rota' => 'info',
                        self::SITUACAO_ENCAMINHADO => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('situacao_entrega')
                    ->label('Situação da Entrega')
                    ->options([
                        'Reservado' => 'Reservado',
                        'Em rota' => 'Em rota',
                        'Entregue' => 'Entregue',
                        self::SITUACAO_ENCAMINHADO => self::SITUACAO_ENCAMINHADO,
                    ]),

                SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'entrega' => 'Entrega',
                        'recolhimento' => 'Recolhimento',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $ultimaTransferencia = fn (string $operator): Builder => $query->whereHas(
                            'ultimaTransferencia',
                            fn (Builder $q) => $q
                                ->where('SetorAnterior', $operator, self::SETOR_PATRIMONIO),
                        );

                        return match ($data['value'] ?? null) {
                            'entrega' => $ultimaTransferencia('='),
                            'recolhimento' => $ultimaTransferencia('<>'),
                            default => $query,
                        };
                    }),
            ])
            ->headerActions([
                $this->imprimirAction(),
            ])
            ->recordActions([
                $this->materiaisAction(),
                $this->encaminharAction(),
            ])
            ->toolbarActions([
                $this->encaminharBulkAction(),
            ])
            ->defaultSort('date_time', 'DESC')
            ->emptyStateIcon('heroicon-o-truck')
            ->emptyStateHeading('Nenhum termo pendente de agendamento');
    }

    public static function tipo(Termo $record): string
    {
        return (int) $record->ultimaTransferencia?->SetorAnterior === self::SETOR_PATRIMONIO
            ? 'Entrega'
            : 'Recolhimento';
    }

    private function imprimirAction(): Action
    {
        return Action::make('imprimir')
            ->label('Imprimir')
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->url(route('pedidos.entrega-recolhimento.imprimir'))
            ->openUrlInNewTab();
    }

    private function materiaisAction(): Action
    {
        return TableModalAction::make('materiais')
            ->tooltip('Visualizar materiais')
            ->label('Materiais')
            ->icon(Heroicon::OutlinedListBullet)
            ->modalHeading(fn (Termo $record): string => "Materiais do termo {$record->termo_completo}")
            ->modalContent(fn (Termo $record): HtmlString => new HtmlString(
                Livewire::mount(
                    'patrimonio.materiais-termo-modal',
                    ['termoId' => (int) $record->getKey()],
                    "materiais-termo-{$record->getKey()}",
                )
            ));
    }

    private function encaminharAction(): Action
    {
        return Action::make('encaminhar')
            ->label('Encaminhar para Logística')
            ->icon('heroicon-o-arrow-up-on-square')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Encaminhar para Logística')
            ->modalDescription('A situação de entrega do termo será atualizada para "Encaminhado para Logística".')
            ->disabled(fn (Termo $record): bool => $record->situacao_entrega === self::SITUACAO_ENCAMINHADO)
            ->action(function (Termo $record): void {
                $record->update(['situacao_entrega' => self::SITUACAO_ENCAMINHADO]);

                Notification::make()
                    ->title('Termo encaminhado para Logística.')
                    ->success()
                    ->send();
            });
    }

    private function encaminharBulkAction(): BulkAction
    {
        return BulkAction::make('encaminhar_selecionados')
            ->label('Encaminhar para Logística')
            ->icon('heroicon-o-arrow-up-on-square')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Encaminhar para Logística')
            ->modalDescription('A situação de entrega dos termos selecionados será atualizada para "Encaminhado para Logística".')
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records): void {
                $pendentes = $records->where('situacao_entrega', '<>', self::SITUACAO_ENCAMINHADO);

                if ($pendentes->isEmpty()) {
                    Notification::make()
                        ->title('Os termos selecionados já foram encaminhados.')
                        ->warning()
                        ->send();

                    return;
                }

                Termo::query()
                    ->whereKey($pendentes->pluck('id'))
                    ->update(['situacao_entrega' => self::SITUACAO_ENCAMINHADO]);

                Notification::make()
                    ->title("{$pendentes->count()} termo(s) encaminhado(s) para Logística.")
                    ->success()
                    ->send();
            });
    }

    public static function getQuery(): Builder
    {
        return Termo::query()
            ->with([
                'arquivoDigital',
                'ultimaTransferencia.setorAnteriorRel',
                'ultimaTransferencia.complementoAnteriorRel',
                'ultimaTransferencia.setorAtualRel',
                'ultimaTransferencia.complementoAtualRel',
                'ultimaTransferencia.usuarioRef',
                'ultimoMaterialTransporte.idSolicitacaoRef.idSituacaoRef',
            ])
            ->whereHas('arquivoDigital', function (Builder $arquivoQuery): void {
                $arquivoQuery
                    ->whereIn('situacao', [
                        ArquivoDigital::SITUACAO_PENDENTE,
                        ArquivoDigital::SITUACAO_INVALIDADO,
                    ]);
            })
            ->whereHas('ultimaTransferencia', function (Builder $transferenciaQuery): void {
                $transferenciaQuery
                    ->where(fn (Builder $query): Builder => $query
                        ->where('SetorAnterior', self::SETOR_PATRIMONIO)
                        ->orWhere('SetorAtual', self::SETOR_PATRIMONIO));
            });
    }

    public function render(): View
    {
        return view('egap.livewire.support.table');
    }
}
