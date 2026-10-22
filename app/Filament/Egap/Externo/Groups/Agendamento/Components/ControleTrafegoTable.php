<?php

namespace App\Filament\Egap\Externo\Groups\Agendamento\Components;

use App\Filament\Egap\Externo\Groups\Agendamento\Concerns\FormataSolicitacao;
use App\Filament\Egap\Interno\Groups\Agendamento\Resources\SolicitacaoResource;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Agendamento\Equipe;
use App\Models\egap\Agendamento\Frota;
use App\Models\egap\Agendamento\Recursos;
use App\Models\egap\Agendamento\Solicitacao;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class ControleTrafegoTable extends Component implements HasActions, HasForms, HasTable
{
    use FormataSolicitacao;
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    protected const UNIDADE_SEDE_TJ = 766;

    public function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->query($this->getQuery())
            ->columns([
                TableColumns::text('agendamento', 'Nº', true)
                    ->state(fn (Solicitacao $record): string => $this->agendamentoTexto($record))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('id', 'like', "%{$search}%"))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('id', $direction)),

                TableColumns::text('solicitante', 'Solicitante')
                    ->state(fn (Solicitacao $record): string => $record->idSolicitanteRef?->name ?? '-')
                    ->description(fn (Solicitacao $record): ?string => $record->setorSolicitanteRef?->setor ?? $record->unidadeSolicitanteRef?->unidadeorganizacional)
                    ->tooltip(fn (Solicitacao $record): ?string => $this->contatos($record))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'idSolicitanteRef',
                        fn (Builder $query): Builder => $query->where('name', 'like', "%{$search}%"),
                    ))
                    ->wrap(),

                TableColumns::text('trajeto', 'Trajeto')
                    ->alignLeft()
                    ->state(fn (Solicitacao $record): string => $this->rotaTexto($record))
                    ->description(fn (Solicitacao $record): ?string => collect([
                        filled($saida = $this->formatDataHora($record->data_inicio, $record->hora_inicio)) ? "Saída: {$saida}" : null,
                        filled($volta = $this->formatDataHora($record->data_termino, $record->hora_termino)) ? "Volta: {$volta}" : null,
                    ])->filter()->implode(' • ') ?: null)
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('local_saida', 'like', "%{$search}%"))
                    ->wrap(),

                TableColumns::text('regiaoRef.sigla', 'Região')
                    ->badge()
                    ->color('info')
                    ->placeholder('-'),

                TableColumns::text('recurso', 'Motorista / Veículo')
                    ->state(function (Solicitacao $record): string {
                        $recurso = $record->recursosRef->first();

                        if (! $recurso) {
                            return '-';
                        }

                        return collect([$recurso->condutorRef?->idPessoaRef?->name, $recurso->veiculoRef?->descricao])
                            ->filter()
                            ->implode(' • ') ?: '-';
                    })
                    ->searchable(false)
                    ->wrap(),

                TableColumns::text('processo_sei', 'Processo SEI')
                    ->state(fn (Solicitacao $record): ?string => $this->justificativaDados($record)['num_processo_sei'] ?? null)
                    ->badge()
                    ->color(fn (?string $state): string => filled($state) ? 'success' : 'gray')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? $state : 'Sem processo')
                    ->searchable(false),

                TableColumns::text('idSituacaoRef.Descricao', 'Status')
                    ->badge()
                    ->color(fn (Solicitacao $record): string => $this->statusColor((int) $record->id_situacao))
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('id_situacao')
                    ->label('Situação')
                    ->options([
                        Solicitacao::SITUACAO_EM_ANALISE => 'Em Análise',
                        Solicitacao::SITUACAO_AGENDADO => 'Agendados',
                        Solicitacao::SITUACAO_CONCLUIDO => 'Concluídos',
                        Solicitacao::SITUACAO_CANCELADO => 'Cancelados',
                        Solicitacao::SITUACAO_INVALIDADO => 'Invalidados',
                    ])
                    ->native(false)
                    ->default((string) Solicitacao::SITUACAO_EM_ANALISE)
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query): Builder => $query->where('id_situacao', $data['value']),
                    )),

                SelectFilter::make('unidade')
                    ->label('Unidade')
                    ->options([
                        'sede' => 'Sede (Tribunal de Justiça)',
                        'interior' => 'Interior / Comarcas',
                    ])
                    ->native(false)
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'sede' => $query->where('unidade_solicitante', self::UNIDADE_SEDE_TJ),
                        'interior' => $query->whereNotNull('unidade_solicitante')->where('unidade_solicitante', '<>', self::UNIDADE_SEDE_TJ),
                        default => $query,
                    }),
            ])
            ->recordActions([
                $this->detalheAction(),
                $this->agendarAction(),
                $this->invalidarAction(),
                $this->processoSeiAction(),
                $this->retornarParaAnaliseAction(),
                $this->finalizarAction(),
                $this->cancelarAction(),
            ])
            ->toolbarActions([])
            ->defaultSort('id', 'desc')
            ->emptyStateHeading('Nenhuma solicitação encontrada');
    }

    protected function detalheAction(): Action
    {
        return Action::make('detalhe')
            ->label('Detalhe')
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->modalHeading(fn (Solicitacao $record): string => "Solicitação nº {$record->id}")
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fechar')
            ->modalContent(fn (Solicitacao $record): View => view(
                'egap.livewire.externo.controle-trafego.detalhe',
                [
                    'solicitacao' => $record,
                    'recursos' => $record->recursosRef()->with(['condutorRef.idPessoaRef', 'veiculoRef'])->get(),
                ],
            ));
    }

    protected function agendarAction(): Action
    {
        return Action::make('agendar')
            ->label('Agendar')
            ->icon('heroicon-o-calendar-days')
            ->color('primary')
            ->visible(fn (Solicitacao $record): bool => (int) $record->id_situacao === Solicitacao::SITUACAO_EM_ANALISE)
            ->modalHeading(fn (Solicitacao $record): string => "Agendar solicitação nº {$record->id}")
            ->modalSubmitActionLabel('Confirmar agendamento')
            ->schema([
                Select::make('condutor')
                    ->label('Condutor')
                    ->options(fn (): array => Equipe::query()
                        ->where('ativo', true)
                        ->with('idPessoaRef')
                        ->get()
                        ->pluck('idPessoaRef.name', 'id_pessoa')
                        ->filter()
                        ->all())
                    ->searchable()
                    ->native(false)
                    ->required(),

                Select::make('veiculo')
                    ->label('Veículo')
                    ->options(fn (): array => Frota::query()->where('ativo', true)->pluck('descricao', 'id')->all())
                    ->searchable()
                    ->native(false)
                    ->required(),

                Textarea::make('observacao')
                    ->label('Observação')
                    ->rows(3)
                    ->maxLength(300),
            ])
            ->action(function (Solicitacao $record, array $data): void {
                Recursos::create([
                    'condutor' => $data['condutor'],
                    'veiculo' => $data['veiculo'],
                    'id_solicitacao' => $record->id,
                    'observacao' => $data['observacao'] ?? null,
                ]);

                $record->update(['id_situacao' => Solicitacao::SITUACAO_AGENDADO]);

                Notification::make()
                    ->title("Solicitação nº {$record->id} agendada.")
                    ->success()
                    ->send();
            });
    }

    protected function invalidarAction(): Action
    {
        return Action::make('invalidar')
            ->label('Invalidar')
            ->icon('heroicon-o-no-symbol')
            ->color('danger')
            ->visible(fn (Solicitacao $record): bool => (int) $record->id_situacao === Solicitacao::SITUACAO_EM_ANALISE)
            ->modalHeading(fn (Solicitacao $record): string => "Invalidar solicitação nº {$record->id}")
            ->modalDescription('Informe o motivo da invalidação. O solicitante poderá editar e reenviar a solicitação.')
            ->modalSubmitActionLabel('Confirmar invalidação')
            ->schema([
                Textarea::make('motivo')
                    ->label('Motivo da invalidação')
                    ->required()
                    ->rows(4)
                    ->maxLength(300),
            ])
            ->action(function (Solicitacao $record, array $data): void {
                $record->update([
                    'id_situacao' => Solicitacao::SITUACAO_INVALIDADO,
                    'motivo_cancelamento' => $data['motivo'],
                ]);

                Notification::make()
                    ->title("Solicitação nº {$record->id} invalidada.")
                    ->success()
                    ->send();
            });
    }

    protected function cancelarAction(): Action
    {
        return Action::make('cancelar_administrativo')
            ->label('Cancelar')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (Solicitacao $record): bool => in_array((int) $record->id_situacao, [Solicitacao::SITUACAO_EM_ANALISE, Solicitacao::SITUACAO_AGENDADO], true))
            ->modalHeading(fn (Solicitacao $record): string => "Cancelar solicitação nº {$record->id}")
            ->modalDescription('Informe o motivo do cancelamento. Essa ação não pode ser desfeita.')
            ->modalSubmitActionLabel('Confirmar cancelamento')
            ->schema([
                Textarea::make('motivo')
                    ->label('Motivo do cancelamento')
                    ->required()
                    ->rows(4)
                    ->maxLength(300),
            ])
            ->action(function (Solicitacao $record, array $data): void {
                $record->update([
                    'id_situacao' => Solicitacao::SITUACAO_CANCELADO,
                    'motivo_cancelamento' => $data['motivo'],
                ]);

                Notification::make()
                    ->title("Solicitação nº {$record->id} cancelada.")
                    ->success()
                    ->send();
            });
    }

    protected function retornarParaAnaliseAction(): Action
    {
        return Action::make('retornar_para_analise')
            ->label('Retornar p/ análise')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('warning')
            ->visible(fn (Solicitacao $record): bool => (int) $record->id_situacao === Solicitacao::SITUACAO_AGENDADO)
            ->requiresConfirmation()
            ->modalHeading(fn (Solicitacao $record): string => "Retornar solicitação nº {$record->id} para análise?")
            ->modalDescription('O motorista e o veículo agendados serão desvinculados desta solicitação.')
            ->action(function (Solicitacao $record): void {
                $record->recursosRef()->delete();

                $record->update(['id_situacao' => Solicitacao::SITUACAO_EM_ANALISE]);

                Notification::make()
                    ->title("Solicitação nº {$record->id} retornou para análise.")
                    ->success()
                    ->send();
            });
    }

    protected function finalizarAction(): Action
    {
        return Action::make('finalizar')
            ->label('Finalizar')
            ->icon('heroicon-o-flag')
            ->color('success')
            ->visible(fn (Solicitacao $record): bool => (int) $record->id_situacao === Solicitacao::SITUACAO_AGENDADO)
            ->requiresConfirmation()
            ->modalHeading(fn (Solicitacao $record): string => "Finalizar solicitação nº {$record->id}?")
            ->modalDescription('A viagem será marcada como concluída.')
            ->action(function (Solicitacao $record): void {
                $record->update(['id_situacao' => Solicitacao::SITUACAO_CONCLUIDO]);

                Notification::make()
                    ->title("Solicitação nº {$record->id} finalizada.")
                    ->success()
                    ->send();
            });
    }

    protected function processoSeiAction(): Action
    {
        return Action::make('processo_sei')
            ->label('Processo SEI')
            ->icon('heroicon-o-document-text')
            ->color('gray')
            ->visible(fn (Solicitacao $record): bool => in_array((int) $record->id_situacao, [Solicitacao::SITUACAO_AGENDADO, Solicitacao::SITUACAO_CONCLUIDO], true))
            ->modalHeading(fn (Solicitacao $record): string => "Processo SEI da solicitação nº {$record->id}")
            ->modalSubmitActionLabel('Salvar')
            ->fillForm(fn (Solicitacao $record): array => [
                'num_processo_sei' => $this->justificativaDados($record)['num_processo_sei'] ?? null,
            ])
            ->schema([
                TextInput::make('num_processo_sei')
                    ->label('Número do processo SEI')
                    ->maxLength(50),
            ])
            ->action(function (Solicitacao $record, array $data): void {
                $justificativa = $record->justificativa ?? [];
                $justificativa['num_processo_sei'] = $data['num_processo_sei'];

                $record->update(['justificativa' => $justificativa]);

                Notification::make()
                    ->title('Processo SEI atualizado.')
                    ->success()
                    ->send();
            });
    }

    protected function getQuery(): Builder
    {
        return Solicitacao::query()
            ->where('tipo', Solicitacao::TIPO_VEICULOS)
            ->with([
                'idSituacaoRef',
                'idSolicitanteRef',
                'setorSolicitanteRef',
                'unidadeSolicitanteRef',
                'regiaoRef',
                'recursosRef.condutorRef.idPessoaRef',
                'recursosRef.veiculoRef',
            ]);
    }

    public function render(): View
    {
        return view('egap.livewire.support.table');
    }
}
