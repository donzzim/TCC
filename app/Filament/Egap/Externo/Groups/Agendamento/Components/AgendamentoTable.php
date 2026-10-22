<?php

namespace App\Filament\Egap\Externo\Groups\Agendamento\Components;

use App\Filament\Egap\Externo\Groups\Agendamento\Concerns\FormataSolicitacao;
use App\Filament\Egap\Support\SetorSelecionado;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Filament\Egap\Support\UsuarioSelecionado;
use App\Models\egap\Agendamento\Solicitacao;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
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
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Base da tabela de solicitações de agendamento de veículos do Ambiente Externo
 * (legado: consultar_agendamento.php + api/agendamento.api.php), compartilhada
 * entre {@see MeusAgendamentosTable} (só do usuário atual) e
 * {@see AgendamentosSetorTable} (todo o Setor atual) — a única diferença real
 * entre as duas é {@see limitarAoUsuarioAtual()}, usado em {@see getQuery()}.
 *
 * O usuário e o setor solicitante ainda não têm um vínculo confiável com a
 * sessão autenticada (ver {@see UsuarioSelecionado} e {@see SetorSelecionado}),
 * por isso a página que hospeda esta tabela os identifica manualmente antes de
 * carregá-la.
 */
abstract class AgendamentoTable extends Component implements HasActions, HasForms, HasTable
{
    use FormataSolicitacao;
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    abstract protected function limitarAoUsuarioAtual(): bool;

    #[On('usuario-selecionado')]
    #[On('setor-selecionado')]
    public function atualizarSelecaoAtual(): void
    {
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->query($this->getQuery())
            ->columns([
                TableColumns::text('agendamento', 'Agendamento', true)
                    ->state(fn(Solicitacao $record): string => $this->agendamentoTexto($record))
                    ->sortable(query: fn(Builder $query, string $direction): Builder => $query->orderBy('id', $direction)),

                TableColumns::text('solicitante', 'Solicitação')
                    ->state(fn(Solicitacao $record): string => $record->idSolicitanteRef?->name ?? '-')
                    ->description(fn(Solicitacao $record): ?string => collect([
                        filled($record->date_time) ? 'Solicitado em ' . Carbon::parse($record->date_time)->format('d/m/Y') : null,
                        filled($passageiro = $this->passageiroPrincipal($record)) ? "Para: {$passageiro}" : null,
                    ])->filter()->implode(' • ') ?: null)
                    ->tooltip(fn(Solicitacao $record): ?string => $this->contatos($record))
                    ->wrap(),

                TableColumns::text('trajeto', 'Trajeto')
                    ->alignLeft()
                    ->state(fn(Solicitacao $record): string => $this->rotaTexto($record))
                    ->description(fn(Solicitacao $record): ?string => collect([
                        filled($saida = $this->formatDataHora($record->data_inicio, $record->hora_inicio)) ? "Saída: {$saida}" : null,
                        filled($volta = $this->formatDataHora($record->data_termino, $record->hora_termino)) ? "Volta: {$volta}" : null,
                    ])->filter()->implode(' • ') ?: null)
                    ->wrap(),

                TableColumns::text('justificativa_texto', 'Justificativa')
                    ->state(fn(Solicitacao $record): string => $this->justificativaTexto($record))
                    ->formatStateUsing(fn(?string $state): string => filled($state) ? $state : '-')
                    ->limit(60)
                    ->tooltip(fn(?string $state): ?string => filled($state) ? $state : null)
                    ->description(fn(Solicitacao $record): ?string => collect([
                        $this->motoristaTexto($record),
                        'Tipo: ' . $this->tipoTexto($record),
                    ])->filter()->implode(' • '))
                    ->wrap(),

                TableColumns::text('idSituacaoRef.Descricao', 'Status')
                    ->badge()
                    ->color(fn(Solicitacao $record): string => $this->statusColor((int) $record->id_situacao))
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('id_situacao')
                    ->label('Situação')
                    ->options([
                        Solicitacao::SITUACAO_EM_ANALISE => 'Em Análise',
                        Solicitacao::SITUACAO_AGENDADO => 'Agendados',
                        Solicitacao::SITUACAO_CONCLUIDO => 'Concluído',
                        Solicitacao::SITUACAO_CANCELADO => 'Cancelado',
                        Solicitacao::SITUACAO_INVALIDADO => 'Inválidado',
                    ])
                    ->native(false)
                    ->query(fn(Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn(Builder $builder): Builder => $builder->where('id_situacao', $data['value'])
                    )),
            ])
            ->recordActions([
                $this->verAgendamentoAction(),
                $this->avaliarAction(),
                $this->editarAction(),
                $this->cancelarAction(),
            ])
            ->toolbarActions([])
            ->defaultSort('id', 'desc')
            ->emptyStateHeading('Nenhuma solicitação encontrada');
    }

    protected function cancelarAction(): Action
    {
        return Action::make('cancelar')
            ->label('Cancelar')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn(Solicitacao $record): bool => in_array((int) $record->id_situacao, [Solicitacao::SITUACAO_EM_ANALISE, Solicitacao::SITUACAO_AGENDADO], true))
            ->modalHeading(fn(Solicitacao $record): string => "Cancelar solicitação nº {$record->id}")
            ->modalDescription('Informe o motivo do cancelamento. Essa ação não pode ser desfeita.')
            ->modalSubmitActionLabel('Confirmar cancelamento')
            ->schema([
                Textarea::make('justificativa')
                    ->label('Motivo do cancelamento')
                    ->required()
                    ->rows(4)
                    ->maxLength(300),
            ])
            ->action(function (Solicitacao $record, array $data): void {
                $record->update([
                    'id_situacao' => Solicitacao::SITUACAO_CANCELADO,
                    'motivo_cancelamento' => $data['justificativa'],
                ]);

                Notification::make()
                    ->title("Solicitação nº {$record->id} cancelada.")
                    ->success()
                    ->send();
            });
    }

    /**
     * Reabre a solicitação para análise (id_situacao volta a 6), disponível tanto
     * para quem está em análise quanto para quem foi invalidado — mesmo
     * comportamento do api/edit.api.php do legado, que sempre grava
     * `id_situacao = 6` após a edição.
     */
    protected function editarAction(): Action
    {
        return Action::make('editar')
            ->label('Editar')
            ->icon('heroicon-o-pencil-square')
            ->color('warning')
            ->visible(fn(Solicitacao $record): bool => in_array((int) $record->id_situacao, [Solicitacao::SITUACAO_EM_ANALISE, Solicitacao::SITUACAO_INVALIDADO], true))
            ->modalHeading(fn(Solicitacao $record): string => "Editar solicitação nº {$record->id}")
            ->fillForm(fn(Solicitacao $record): array => [
                'local_saida' => $record->local_saida,
                'local_destino' => $record->local_destino,
                'data_inicio' => $record->data_inicio,
                'hora_inicio' => $record->hora_inicio,
                'data_termino' => $record->data_termino,
                'hora_termino' => $record->hora_termino,
            ])
            ->schema([
                TextInput::make('local_saida')
                    ->label('Local de saída')
                    ->required()
                    ->maxLength(255),
                TextInput::make('local_destino')
                    ->label('Local de destino')
                    ->required()
                    ->maxLength(255),
                DatePicker::make('data_inicio')
                    ->label('Data início')
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                TimePicker::make('hora_inicio')
                    ->label('Hora início')
                    ->seconds(false),
                DatePicker::make('data_termino')
                    ->label('Data término (viagens de ida e volta)')
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                TimePicker::make('hora_termino')
                    ->label('Hora término')
                    ->seconds(false),
            ])
            ->action(function (Solicitacao $record, array $data): void {
                $payload = [
                    'id_situacao' => Solicitacao::SITUACAO_EM_ANALISE,
                    'local_saida' => $data['local_saida'],
                    'local_destino' => $data['local_destino'],
                    'data_inicio' => $data['data_inicio'],
                    'hora_inicio' => $data['hora_inicio'],
                ];

                if (filled($data['data_termino'] ?? null)) {
                    $payload['data_termino'] = $data['data_termino'];
                    $payload['hora_termino'] = $data['hora_termino'];
                }

                $justificativa = $record->justificativa ?? [];
                $justificativa['destino'] = $data['local_destino'];
                $payload['justificativa'] = $justificativa;

                $record->update($payload);

                Notification::make()
                    ->title("Solicitação nº {$record->id} editada.")
                    ->success()
                    ->send();
            });
    }

    protected function avaliarAction(): Action
    {
        return Action::make('avaliar')
            ->label('Avaliar')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn(Solicitacao $record): bool => (int) $record->id_situacao === Solicitacao::SITUACAO_AGENDADO)
            ->modalHeading(fn(Solicitacao $record): string => "Avaliar solicitação nº {$record->id}")
            ->modalDescription('Avalie o atendimento recebido e, se desejar, deixe uma observação.')
            ->schema([
                Select::make('rating')
                    ->label('Avaliação')
                    ->options([
                        5 => '★★★★★ Excelente',
                        4 => '★★★★ Bom',
                        3 => '★★★ Regular',
                        2 => '★★ Ruim',
                        1 => '★ Péssimo',
                    ])
                    ->native(false)
                    ->required(),
                Textarea::make('obs')
                    ->label('Observações')
                    ->rows(3)
                    ->maxLength(300),
            ])
            ->action(function (Solicitacao $record, array $data): void {
                $record->update([
                    'id_situacao' => Solicitacao::SITUACAO_CONCLUIDO,
                    'finalizar' => json_encode([
                        'data' => now()->toDateString(),
                        'hora' => now()->format('H:i'),
                        'obs' => $data['obs'] ?? '',
                        'rating' => $data['rating'],
                    ], JSON_UNESCAPED_UNICODE),
                ]);

                Notification::make()
                    ->title("Solicitação nº {$record->id} avaliada com sucesso!")
                    ->success()
                    ->send();
            });
    }

    protected function verAgendamentoAction(): Action
    {
        return Action::make('ver_agendamento')
            ->label('Ver Agendamento')
            ->icon('heroicon-o-truck')
            ->color('gray')
            ->visible(fn(Solicitacao $record): bool => (int) $record->id_situacao === Solicitacao::SITUACAO_AGENDADO)
            ->modalHeading(fn(Solicitacao $record): string => "Agendamento da solicitação # {$record->id}")
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fechar')
            ->modalContent(fn(Solicitacao $record): View => view(
                'egap.livewire.externo.detalhes-agendamento',
                ['recursos' => $record->recursosRef()->with(['condutorRef.idPessoaRef', 'veiculoRef'])->get()],
            ));
    }

    protected function getQuery(): Builder
    {
        $setorId = SetorSelecionado::resolverAtual();
        $usuarioId = $this->limitarAoUsuarioAtual() ? UsuarioSelecionado::resolverAtual() : null;

        return Solicitacao::query()
            ->where('tipo', Solicitacao::TIPO_VEICULOS)
            ->when(
                blank($setorId) || ($this->limitarAoUsuarioAtual() && blank($usuarioId)),
                fn(Builder $query) => $query->whereKey(-1),
                fn(Builder $query) => $query
                    ->where('setor_solicitante', $setorId)
                    ->when($usuarioId, fn(Builder $query) => $query->where('id_solicitante', $usuarioId))
            )
            ->with(['idSituacaoRef', 'idSolicitanteRef']);
    }

    public function render(): View
    {
        return view('egap.livewire.support.table');
    }
}
