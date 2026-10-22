<?php

namespace App\Filament\Egap\Externo\Groups\Patrimonio\Components;

use App\Filament\Egap\Support\SetorSelecionado;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Patrimonio\BensMoveis\ArquivoDigital;
use App\Models\egap\Patrimonio\BensMoveis\Termo;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Lista os Termos de Responsabilidade de transferência de bens que envolvem o
 * setor do usuário autenticado do Ambiente Externo, seja como origem ou como
 * destino (legado: transferir_material.php + api/situacao_materiais.api.php),
 * permitindo assinar eletronicamente o recebimento, cancelar a transferência ou
 * anexar o comprovante de retirada/entrega.
 *
 * A assinatura eletrônica efetiva a transferência (mesma regra usada na tela de
 * Validar Termos do admin, via {@see ArquivoDigital::validar()}), dispensando a
 * validação manual pela Seção de Patrimônio quando o próprio setor destinatário
 * confirma o recebimento.
 */
class MovimentacaoDeMateriaisTable extends Component implements HasForms, HasTable, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    private const SITUACOES_EM_ABERTO = [ArquivoDigital::SITUACAO_PENDENTE, ArquivoDigital::SITUACAO_INVALIDADO];

    public ?int $setorAtual = null;

    public function mount(): void
    {
        $this->setorAtual = SetorSelecionado::resolverAtual();
    }

    #[On('setor-selecionado')]
    public function atualizarSetorSelecionado(int $setor): void
    {
        $this->setorAtual = $setor;
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->query($this->getQuery())
            ->columns([
                TableColumns::text('num_termo', 'Termo')
                    ->formatStateUsing(fn(Termo $record): string => $record->termo_completo)
                    ->description(fn(Termo $record): ?string => $record->date_time?->format('d/m/Y'))
                    ->searchable(['num_termo', 'ano_termo'])
                    ->badge()
                    ->color('primary'),

                TableColumns::text('ultimaTransferencia.setorAnteriorRel.Setor', 'Setor de Origem')
                    ->wrap()
                    ->description(fn(Termo $record): ?string => $record->ultimaTransferencia?->complementoAnteriorRel?->descricao),

                TableColumns::text('ultimaTransferencia.setorAtualRel.Setor', 'Setor de Destino')
                    ->wrap()
                    ->description(fn(Termo $record): ?string => $record->ultimaTransferencia?->complementoAtualRel?->descricao),

                TableColumns::text('ultimaTransferencia.usuarioRef.name', 'Transferido por')
                    ->wrap(),

                TableColumns::text('transferencias_count', 'Qtd. de Bens')
                    ->counts('transferencias'),

                TableColumns::text('arquivoDigital.situacao', 'Situação do Termo')
                    ->formatStateUsing(fn($state): string => ArquivoDigital::situacaoLabel($state))
                    ->description(fn(Termo $record): ?string => in_array((int) ($record->arquivoDigital?->situacao ?? -1), [ArquivoDigital::SITUACAO_INVALIDADO, ArquivoDigital::SITUACAO_CANCELADO], true)
                        ? $record->arquivoDigital?->observacao
                        : null)
                    ->wrap()
                    ->badge()
                    ->color(fn($state): string => ArquivoDigital::situacaoColor($state)),
            ])
            ->filters([
                SelectFilter::make('situacao')
                    ->label('Situação')
                    ->options([
                        'pendentes' => 'Pendentes',
                        'assinados' => 'Assinados',
                        'cancelados' => 'Cancelados',
                    ])
                    ->native(false)
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'pendentes' => $query->whereHas(
                                'arquivoDigital',
                                fn(Builder $query) => $query->whereIn('situacao', self::SITUACOES_EM_ABERTO)
                            ),
                            'assinados' => $query->whereHas(
                                'arquivoDigital',
                                fn(Builder $query) => $query->where('situacao', ArquivoDigital::SITUACAO_VALIDADO)
                            ),
                            'cancelados' => $query->whereHas(
                                'arquivoDigital',
                                fn(Builder $query) => $query->where('situacao', ArquivoDigital::SITUACAO_CANCELADO)
                            ),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                $this->visualizarTermoAction(),
                $this->situacaoEntregaAction(),
                ActionGroup::make([
                    $this->assinarEletronicamenteAction(),
                    $this->enviarComprovanteAction(),
                    $this->cancelarTermoAction(),
                ])
                    ->hiddenLabel()
                    ->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->toolbarActions([])
            ->defaultSort('id', 'desc')
            ->emptyStateHeading(
                blank($this->setorAtual)
                    ? 'Não foi possível identificar o setor do usuário atual.'
                    : 'Nenhuma movimentação encontrada'
            );
    }

    private function visualizarTermoAction(): Action
    {
        return Action::make('visualizar_termo')
            ->label('Visualizar Termo')
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->url(fn(Termo $record): ?string => filled($record->arquivoDigital?->arquivo_digital)
                ? config('app.egap') . $record->arquivoDigital->arquivo_digital
                : null)
            ->openUrlInNewTab()
            ->disabled(fn(Termo $record): bool => blank($record->arquivoDigital?->arquivo_digital))
            ->tooltip(fn(Termo $record): ?string => blank($record->arquivoDigital?->arquivo_digital)
                ? 'Nenhum arquivo digital anexado ainda.'
                : null);
    }

    private function situacaoEntregaAction(): Action
    {
        return Action::make('situacao_entrega')
            ->label('Situação da Entrega')
            ->icon('heroicon-o-clock')
            ->color('gray')
            ->modalHeading(fn(Termo $record): string => "Entrega - Termo n° {$record->termo_completo}")
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fechar')
            ->modalContent(fn(Termo $record): View => view(
                'egap.filament.pages.externo.patrimonio.partials.situacao-entrega',
                ['termo' => $record],
            ));
    }

    private function assinarEletronicamenteAction(): Action
    {
        return Action::make('assinar_eletronicamente')
            ->label('Assinar Eletronicamente')
            ->icon('heroicon-o-pencil-square')
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription('Ao assinar, você confirma o recebimento dos bens no setor de destino e a transferência será efetivada.')
            ->visible(fn(Termo $record): bool => $this->podeAssinar($record))
            ->action(function (Termo $record): void {
                $validado = $record->arquivoDigital?->validar((int) auth()->id()) ?? false;

                if (! $validado) {
                    Notification::make()
                        ->title('Não foi possível assinar o termo.')
                        ->body('Verifique se o termo ainda está pendente e possui transferências associadas.')
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Termo assinado com sucesso.')
                    ->body('A transferência foi efetivada.')
                    ->success()
                    ->send();
            });
    }

    private function enviarComprovanteAction(): Action
    {
        return Action::make('enviar_comprovante')
            ->label('Enviar Comprovante')
            ->icon('heroicon-o-document-arrow-up')
            ->color('gray')
            ->visible(fn(Termo $record): bool => in_array(
                (int) ($record->arquivoDigital?->situacao ?? -1),
                self::SITUACOES_EM_ABERTO,
                true
            ))
            ->schema([
                FileUpload::make('arquivo')
                    ->label('Comprovante de retirada/entrega (PDF)')
                    ->required()
                    ->acceptedFileTypes(['application/pdf'])
                    ->disk('egap')
                    ->directory('images/termos')
                    ->maxSize(10240),
            ])
            ->action(function (Termo $record, array $data): void {
                $record->arquivoDigital?->fill([
                    'arquivo_digital' => $data['arquivo'],
                ])->save();

                Notification::make()
                    ->title('Comprovante anexado com sucesso.')
                    ->success()
                    ->send();
            });
    }

    private function cancelarTermoAction(): Action
    {
        return Action::make('cancelar_termo')
            ->label('Cancelar Termo')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn(Termo $record): bool => in_array(
                (int) ($record->arquivoDigital?->situacao ?? -1),
                self::SITUACOES_EM_ABERTO,
                true
            ))
            ->schema([
                Textarea::make('justificativa')
                    ->label('Justificativa do cancelamento')
                    ->required()
                    ->rows(4)
                    ->maxLength(300)
                    ->placeholder('Ex: Transferência solicitada equivocadamente'),
            ])
            ->action(function (Termo $record, array $data): void {
                $record->arquivoDigital?->fill([
                    'situacao' => ArquivoDigital::SITUACAO_CANCELADO,
                    'observacao' => $data['justificativa'],
                    'data_validacao' => now(),
                    'validado_por' => auth()->id(),
                ])->save();

                Notification::make()
                    ->title('Termo cancelado com sucesso.')
                    ->success()
                    ->send();
            });
    }

    private function podeAssinar(Termo $record): bool
    {
        $situacao = (int) ($record->arquivoDigital?->situacao ?? -1);

        if (! in_array($situacao, self::SITUACOES_EM_ABERTO, true)) {
            return false;
        }

        return filled($this->setorAtual)
            && (int) $record->ultimaTransferencia?->SetorAtual === $this->setorAtual;
    }

    private function getQuery(): Builder
    {
        return Termo::query()
            ->when(
                blank($this->setorAtual),
                fn(Builder $query) => $query->whereKey(-1),
                fn(Builder $query) => $query->whereHas('transferencias', function (Builder $query): void {
                    $query->where('SetorAtual', $this->setorAtual)
                        ->orWhere('SetorAnterior', $this->setorAtual);
                })
            )
            ->with([
                'arquivoDigital',
                'ultimaTransferencia.setorAnteriorRel',
                'ultimaTransferencia.setorAtualRel',
                'ultimaTransferencia.complementoAnteriorRel',
                'ultimaTransferencia.complementoAtualRel',
                'ultimaTransferencia.usuarioRef',
            ]);
    }

    public function render(): View
    {
        return view('egap.livewire.support.table');
    }
}
