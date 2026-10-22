<?php

namespace App\Filament\Egap\Externo\Groups\Patrimonio\Components;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\ValidarTermoResource;
use App\Filament\Egap\Support\SetorSelecionado;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Mail\Egap\Patrimonio\ConfirmacaoSolicitacaoInclusaoBemMail;
use App\Mail\Egap\Patrimonio\SolicitacaoInclusaoBemMail;
use App\Models\egap\Cadastro\ComplementoSetor;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\Patrimonio\BensMoveis\ArquivoDigital;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use App\Models\egap\Patrimonio\BensMoveis\Termo;
use App\Models\egap\Patrimonio\BensMoveis\TransferenciaBemMovel;
use App\Models\egap\UserEgap;
use App\Services\Egap\Mobile\ConferenciaBensService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\On;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Lista os bens patrimoniais lotados no setor do usuário autenticado do
 * Ambiente Externo (legado: bens.php + transferencia.api.php +
 * bensainventariar_operacoes.api.php), permitindo editar dados básicos do
 * bem, solicitar transferência, solicitar inclusão de um bem não cadastrado
 * e, quando há um Inventário Online em andamento para o setor, confirmar a
 * localização ou registrar bens não localizados e finalizar o inventário do
 * setor.
 *
 * A transferência gera um Termo de Responsabilidade pendente (mesmo fluxo
 * usado em {@see ValidarTermoResource}): a localização do bem só é efetivada
 * quando o termo é validado pela Seção de Patrimônio, então esta tela não
 * altera Setor/Unidade do bem diretamente.
 *
 * A conferência do Inventário Online (localizar/não localizado/finalizar)
 * reaproveita {@see ConferenciaBensService}, criado originalmente para o app
 * mobile: nenhuma alteração foi feita naquele serviço, apenas consumo dos
 * seus métodos públicos, para não impactar o fluxo mobile.
 *
 * Não replicado do legado: a reconfirmação de senha antes de assinar/editar
 * (mesmo padrão adotado em {@see LevantamentoComissaoTable}) e o cadastro em
 * lote no modal "Incluir Bem" (aqui, um bem por solicitação).
 */
class BensNoSetorTable extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    private const SITUACOES_ATIVAS = [1, 7, 8];

    private const SITUACAO_EM_TRANSFERENCIA = 7;

    private const EMAIL_SECAO_PATRIMONIO = 'patrimonio@tjes.jus.br';

    public ?int $setorAtual = null;

    private ?array $conferencia = null;

    private bool $conferenciaCarregada = false;

    public function mount(): void
    {
        $this->setorAtual = SetorSelecionado::resolverAtual();
    }

    #[On('setor-selecionado')]
    public function atualizarSetorSelecionado(int $setor): void
    {
        $this->setorAtual = $setor;
        $this->resetConferencia();
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->query(self::queryDoSetor($this->setorAtual))
            ->columns([
                TableColumns::text('NumPatrimonio', 'Patrimônio', isFirstColumn: true, isIdColumn: true),

                TableColumns::text('NumerodePatAnterior', 'Patrimônio Anterior')
                    ->badge()
                    ->color('gray'),

                TableColumns::text('Descricao', 'Material')
                    ->wrap()
                    ->description(fn(BemMovel $record): ?string => collect([
                        $record->marcaRef?->Descricao,
                        $record->modeloRef?->descricao,
                    ])->filter()->implode(' / ') ?: null),

                TableColumns::text('complementoSetorRef.descricao', 'Complemento do Setor')
                    ->wrap(),

                TableColumns::text('EstadodeConservacao', 'Estado de Conservação')
                    ->badge()
                    ->color(fn(?string $state): string => match ($state) {
                        'ÓTIMO', 'BOM' => 'success',
                        'REGULAR' => 'warning',
                        'RUIM', 'SUCATA' => 'danger',
                        default => 'gray',
                    }),

                TableColumns::text('situacaoBemRef.descricao', 'Situação')
                    ->badge()
                    ->color('gray'),

                TableColumns::text('sit_inventario', 'Situação Inventário')
                    ->formatStateUsing(fn(?string $state): string => strtoupper(trim((string) $state)) ?: 'A INVENTARIAR')
                    ->badge()
                    ->color(fn(?string $state): string => match (strtoupper(trim((string) $state))) {
                        'LOCALIZADO' => 'success',
                        'NÃO LOCALIZADO', 'NAO LOCALIZADO' => 'danger',
                        'EM TRANSFERENCIA', 'EM TRANSFERÊNCIA' => 'warning',
                        default => 'gray',
                    })
                    ->visible(fn(): bool => $this->inventarioAtivoParaSetor()),

                TextColumn::make('termo_pendente')
                    ->label('Termo')
                    ->getStateUsing(fn(BemMovel $record): ?string => self::termoPendente($record))
                    ->badge()
                    ->color('warning')
                    ->default('-')
                    ->url(fn(BemMovel $record): ?string => self::termoPendente($record)
                        ? route('termo.imprimir', ['id' => $record->ultimaTransferencia->Termo])
                        : null)
                    ->openUrlInNewTab(),
            ])
            ->filters([
                SelectFilter::make('ComplementoSetor')
                    ->label('Complemento do Setor')
                    ->options(fn(): array => ComplementoSetor::query()
                        ->whereIn('id', BemMovel::query()
                            ->where('Setor', $this->setorAtual)
                            ->whereNotNull('ComplementoSetor')
                            ->distinct()
                            ->pluck('ComplementoSetor'))
                        ->orderBy('descricao')
                        ->pluck('descricao', 'id')
                        ->toArray()),
            ])
            ->recordActions([
                $this->editarAction(),
            ])
            ->toolbarActions([
                $this->transferirBulkAction(),
                $this->confirmarLocalizacaoBulkAction(),
                $this->bemNaoLocalizadoBulkAction(),
            ])
            ->checkIfRecordIsSelectableUsing(
                fn(BemMovel $record): bool => (int) $record->SituacaoBem !== self::SITUACAO_EM_TRANSFERENCIA
            )
            ->selectCurrentPageOnly()
            ->defaultSort('Descricao')
            ->emptyStateHeading(
                blank($this->setorAtual)
                    ? 'Não foi possível identificar o setor do usuário atual.'
                    : 'Nenhum bem encontrado no setor'
            );
    }

    private function editarAction(): Action
    {
        return Action::make('editar')
            ->label('Editar')
            ->icon('heroicon-o-pencil-square')
            ->color('gray')
            ->modalHeading(fn(BemMovel $record): string => "Editar bem - Patrimônio {$record->NumPatrimonio}")
            ->disabled(fn(BemMovel $record): bool => (int) $record->SituacaoBem === self::SITUACAO_EM_TRANSFERENCIA)
            ->tooltip(fn(BemMovel $record): ?string => (int) $record->SituacaoBem === self::SITUACAO_EM_TRANSFERENCIA
                ? 'Não é possível editar um patrimônio em transferência.'
                : null)
            ->fillForm(fn(BemMovel $record): array => [
                'NumerodeSerie' => $record->NumerodeSerie,
                'ComplementoSetor' => $record->ComplementoSetor,
                'EstadodeConservacao' => $record->EstadodeConservacao,
            ])
            ->schema([
                TextInput::make('NumerodeSerie')
                    ->label('Número de série')
                    ->maxLength(50),

                Select::make('ComplementoSetor')
                    ->label('Complemento do setor')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(fn(): array => ComplementoSetor::query()
                        ->orderBy('descricao')
                        ->pluck('descricao', 'id')
                        ->toArray()),

                Select::make('EstadodeConservacao')
                    ->label('Estado de conservação')
                    ->required()
                    ->native(false)
                    ->options([
                        'ÓTIMO' => 'ÓTIMO',
                        'BOM' => 'BOM',
                        'REGULAR' => 'REGULAR',
                        'RUIM' => 'RUIM',
                    ]),
            ])
            ->action(function (BemMovel $record, array $data): void {
                $record->update([
                    'NumerodeSerie' => $data['NumerodeSerie'],
                    'ComplementoSetor' => $data['ComplementoSetor'],
                    'EstadodeConservacao' => $data['EstadodeConservacao'],
                ]);

                Notification::make()
                    ->title('Bem atualizado com sucesso.')
                    ->success()
                    ->send();
            });
    }

    private function transferirBulkAction(): BulkAction
    {
        return BulkAction::make('transferir_bens')
            ->label('Transferir Bens')
            ->icon('heroicon-o-arrows-right-left')
            ->color('primary')
            ->modalHeading('Transferir bens selecionados')
            ->modalDescription('Um Termo de Responsabilidade será gerado e ficará pendente até a assinatura eletrônica do setor de destino.')
            ->modalSubmitActionLabel('Confirmar transferência')
            ->schema([
                Select::make('UnidadeJudiciaria')
                    ->label('Unidade Judiciária de destino')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->native(false)
                    ->options(fn(): array => Setores::query()
                        ->whereColumn('id', 'codigopai')
                        ->orderBy('unidadeorganizacional')
                        ->pluck('unidadeorganizacional', 'codigopai')
                        ->toArray())
                    ->afterStateUpdated(fn(Set $set) => $set('Setor', null)),

                Select::make('Setor')
                    ->label('Setor de destino')
                    ->placeholder(fn(Get $get) => blank($get('UnidadeJudiciaria'))
                        ? 'Selecione primeiro a unidade judiciária'
                        : 'Selecione o setor')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->native(false)
                    ->options(fn(Get $get): array => Setores::query()
                        ->when(
                            $get('UnidadeJudiciaria'),
                            fn($query, $codigoPai) => $query->where('codigopai', $codigoPai)
                        )
                        ->orderBy('setor')
                        ->pluck('setor', 'id')
                        ->toArray())
                    ->disabled(fn(Get $get): bool => blank($get('UnidadeJudiciaria')))
                    ->afterStateUpdated(fn(Set $set) => $set('ComplementoSetor', null)),

                Select::make('ComplementoSetor')
                    ->label('Complemento do setor de destino')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(function (Get $get): array {
                        $setorId = $get('Setor');

                        $usados = filled($setorId)
                            ? BemMovel::query()
                            ->where('Setor', $setorId)
                            ->whereNotNull('ComplementoSetor')
                            ->distinct()
                            ->pluck('ComplementoSetor')
                            : collect();

                        return ComplementoSetor::query()
                            ->when($usados->isNotEmpty(), fn($query) => $query->whereIn('id', $usados))
                            ->orderBy('descricao')
                            ->pluck('descricao', 'id')
                            ->toArray();
                    })
                    ->disabled(fn(Get $get): bool => blank($get('Setor'))),

                Textarea::make('justificativa')
                    ->label('Justificativa da transferência')
                    ->required()
                    ->rows(4)
                    ->maxLength(300)
                    ->placeholder('Descreva o motivo da transferência dos bens selecionados.'),
            ])
            ->deselectRecordsAfterCompletion()
            ->action(fn(EloquentCollection $records, array $data) => $this->transferirBens($records, $data));
    }

    private function transferirBens(EloquentCollection $records, array $data): void
    {
        $bens = $records->filter(
            fn(BemMovel $bem): bool => (int) $bem->SituacaoBem !== self::SITUACAO_EM_TRANSFERENCIA
        );

        if ($bens->isEmpty()) {
            Notification::make()
                ->title('Os bens selecionados já estão em transferência.')
                ->warning()
                ->send();

            return;
        }

        try {
            $termo = DB::transaction(function () use ($bens, $data): Termo {
                $ano = (int) now()->year;

                $ultimoTermo = Termo::query()
                    ->where('ano_termo', $ano)
                    ->orderByDesc('num_termo')
                    ->lockForUpdate()
                    ->first(['num_termo']);

                $termo = Termo::query()->create([
                    'num_termo' => ((int) $ultimoTermo?->num_termo) + 1,
                    'ano_termo' => $ano,
                    'situacao_entrega' => 'Reservado',
                ]);

                $termo->arquivoDigital()->create([
                    'situacao' => ArquivoDigital::SITUACAO_PENDENTE,
                    'web' => 1,
                    'observacao' => "Justificativa do solicitante: {$data['justificativa']}",
                ]);

                $unidadeAtual = Setores::query()->find($data['Setor'])?->codigodauo ?? $data['UnidadeJudiciaria'];

                foreach ($bens as $bem) {
                    TransferenciaBemMovel::query()->create([
                        'NumPatrimonio' => $bem->NumPatrimonio,
                        'UnidadeAnterior' => $bem->UnidadeJudiciaria,
                        'SetorAnterior' => $bem->Setor,
                        'ComplementoAnterior' => $bem->ComplementoSetor,
                        'AndarAnterior' => $bem->AndarSetor,
                        'UnidadeAtual' => $unidadeAtual,
                        'SetorAtual' => $data['Setor'],
                        'ComplementoAtual' => $data['ComplementoSetor'],
                        'Termo' => $termo->id,
                    ]);
                }

                return $termo;
            });
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Não foi possível transferir os bens.')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title("Transferência registrada no termo {$termo->num_termo}/{$termo->ano_termo}.")
            ->body('A transferência ficará pendente até a assinatura eletrônica do setor de destino.')
            ->success()
            ->send();
    }

    private function confirmarLocalizacaoBulkAction(): BulkAction
    {
        return BulkAction::make('confirmar_localizacao')
            ->label('Confirmar a Localização')
            ->icon('heroicon-o-hand-thumb-up')
            ->color('success')
            ->modalHeading('Confirmar Localização do(s) Bem(ns)')
            ->modalDescription('Ao confirmar, os bens selecionados serão registrados como localizados na conferência do Inventário Online deste setor.')
            ->requiresConfirmation()
            ->visible(fn(): bool => $this->inventarioAtivoParaSetor())
            ->deselectRecordsAfterCompletion()
            ->action(fn(EloquentCollection $records) => $this->confirmarLocalizacao($records));
    }

    private function confirmarLocalizacao(EloquentCollection $records): void
    {
        $scope = $this->scope();

        if ($scope === null) {
            Notification::make()
                ->title('Não foi possível identificar o setor do usuário atual.')
                ->danger()
                ->send();

            return;
        }

        $service = app(ConferenciaBensService::class);
        $localizados = 0;
        $falhas = 0;

        foreach ($records as $record) {
            try {
                $resultado = $service->localizar($scope, ['bem_id' => $record->getKey()]);

                if (($resultado['status'] ?? null) === 'localizado') {
                    $localizados++;
                } else {
                    $falhas++;
                }
            } catch (HttpException | NotFoundHttpException) {
                $falhas++;
            }
        }

        $this->resetConferencia();
        $this->resetTable();

        if ($localizados === 0) {
            Notification::make()
                ->title('Nenhum bem pôde ser localizado.')
                ->body('Os bens selecionados já podem estar conferidos ou não elegíveis para conferência.')
                ->warning()
                ->send();

            return;
        }

        Notification::make()
            ->title($localizados === 1 ? '1 bem localizado!' : "{$localizados} bens localizados!")
            ->body($falhas > 0 ? "{$falhas} bem(ns) não puderam ser processados." : null)
            ->success()
            ->send();
    }

    private function bemNaoLocalizadoBulkAction(): BulkAction
    {
        return BulkAction::make('bem_nao_localizado')
            ->label('Bem Não Localizado')
            ->icon('heroicon-o-hand-thumb-down')
            ->color('danger')
            ->modalHeading('Bens Não Localizados')
            ->modalDescription('Caso haja algum documento comprovando a transferência do(s) bem(ns), favor encaminhar para o e-mail da Seção de Patrimônio.')
            ->modalSubmitActionLabel('Salvar')
            ->visible(fn(): bool => $this->inventarioAtivoParaSetor())
            ->form([
                Textarea::make('justificativa')
                    ->label('Justificativa')
                    ->placeholder('Ex: Bem(ns) não localizado(s) no setor')
                    ->required()
                    ->rows(4)
                    ->maxLength(500),
            ])
            ->deselectRecordsAfterCompletion()
            ->action(fn(EloquentCollection $records, array $data) => $this->registrarNaoLocalizados($records, $data['justificativa']));
    }

    private function registrarNaoLocalizados(EloquentCollection $records, string $justificativa): void
    {
        $scope = $this->scope();

        if ($scope === null) {
            Notification::make()
                ->title('Não foi possível identificar o setor do usuário atual.')
                ->danger()
                ->send();

            return;
        }

        try {
            app(ConferenciaBensService::class)->naoLocalizados($scope, $records->pluck('id')->all(), $justificativa);
        } catch (HttpException | NotFoundHttpException $exception) {
            Notification::make()
                ->title('Não foi possível registrar a informação.')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->resetConferencia();
        $this->resetTable();

        Notification::make()
            ->title('Informação registrada! Materiais atualizados.')
            ->success()
            ->send();
    }

    public function incluirBemAction(): Action
    {
        return Action::make('incluirBem')
            ->label('Incluir Bem')
            ->icon('heroicon-o-plus-circle')
            ->color('info')
            ->modalWidth(Width::SixExtraLarge)
            ->modalHeading('Solicitar Inclusão de Bem')
            ->modalDescription('Solicite a inclusão de um bem patrimonial ainda não cadastrado neste setor. A Seção de Patrimônio receberá a solicitação por e-mail para análise.')
            ->modalSubmitActionLabel('Solicitar Inclusão')
            ->schema(fn(Schema $schema): Schema => $schema->columns(12)->components([
                TextInput::make('NumPatrimonio')
                    ->label('Nº Patrimônio')
                    ->required()
                    ->maxLength(50)
                    ->columnSpan(['default' => 12, 'md' => 4]),

                TextInput::make('NumPatrimonioAntigo')
                    ->label('Nº do Patrimônio Antigo')
                    ->maxLength(50)
                    ->columnSpan(['default' => 12, 'md' => 4]),

                TextInput::make('NumeroSerie')
                    ->label('Nº de Série')
                    ->maxLength(50)
                    ->columnSpan(['default' => 12, 'md' => 4]),

                TextInput::make('Marca')
                    ->label('Marca')
                    ->maxLength(100)
                    ->columnSpan(['default' => 12, 'md' => 6]),

                TextInput::make('Modelo')
                    ->label('Modelo')
                    ->maxLength(100)
                    ->columnSpan(['default' => 12, 'md' => 6]),

                Select::make('ComplementoSetor')
                    ->label('Complemento do setor')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(fn(): array => ComplementoSetor::query()
                        ->orderBy('descricao')
                        ->pluck('descricao', 'id')
                        ->toArray())
                    ->columnSpan(['default' => 12, 'md' => 8]),

                Select::make('EstadodeConservacao')
                    ->label('Estado de conservação')
                    ->required()
                    ->native(false)
                    ->options([
                        'ÓTIMO' => 'ÓTIMO',
                        'BOM' => 'BOM',
                        'REGULAR' => 'REGULAR',
                        'RUIM' => 'RUIM',
                    ])
                    ->columnSpan(['default' => 12, 'md' => 4]),

                Textarea::make('Descricao')
                    ->label('Descrição do bem')
                    ->required()
                    ->rows(3)
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]))
            ->action(fn(array $data) => $this->solicitarInclusaoBem($data));
    }

    private function solicitarInclusaoBem(array $data): void
    {
        $usuario = UserEgap::currentAuthenticated();

        if ($usuario === null || blank($usuario->email)) {
            Notification::make()
                ->title('Não foi possível identificar seu usuário para enviar a solicitação.')
                ->danger()
                ->send();

            return;
        }

        $setorNome = Setores::query()->find($this->setorAtual)?->setor ?? "Setor {$this->setorAtual}";

        $bem = [
            ...$data,
            'ComplementoSetorDescricao' => ComplementoSetor::query()->find($data['ComplementoSetor'])?->descricao,
        ];

        Mail::to(self::EMAIL_SECAO_PATRIMONIO)->send(
            new SolicitacaoInclusaoBemMail($bem, (string) $usuario->name, $setorNome, $usuario->email)
        );

        Mail::to($usuario->email)->send(
            new ConfirmacaoSolicitacaoInclusaoBemMail($bem, $setorNome)
        );

        Notification::make()
            ->title('Solicitação enviada!')
            ->body('Aguarde a confirmação da Seção de Patrimônio por e-mail.')
            ->success()
            ->send();
    }

    public function finalizarInventarioAction(): Action
    {
        return Action::make('finalizarInventario')
            ->label('Finalizar Inventário')
            ->icon('heroicon-o-check-circle')
            ->color('primary')
            ->modalHeading('Finalizar Inventário')
            ->modalDescription('Todos os bens foram inventariados. Finalize o inventário de seu setor clicando em "Finalizar"!')
            ->modalSubmitActionLabel('Finalizar')
            ->visible(fn(): bool => $this->podeFinalizarInventario())
            ->action(function (): void {
                $scope = $this->scope();

                if ($scope === null) {
                    Notification::make()
                        ->title('Não foi possível identificar o setor do usuário atual.')
                        ->danger()
                        ->send();

                    return;
                }

                try {
                    app(ConferenciaBensService::class)->finalizar($scope);
                } catch (HttpException $exception) {
                    Notification::make()
                        ->title('Não foi possível finalizar o inventário.')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                $this->resetConferencia();
                $this->resetTable();

                Notification::make()
                    ->title('Inventário finalizado com sucesso!')
                    ->success()
                    ->send();
            });
    }

    /**
     * Reflete a condição do legado ($statusInventario != 'Finalizado' &&
     * verificaInventarioSetor() != 'Finalizado'): existe um Inventário Online
     * ainda não concluído e a atividade deste setor, se houver, não está
     * finalizada.
     */
    public function inventarioAtivoParaSetor(): bool
    {
        $conferencia = $this->conferencia();

        if ($conferencia === null) {
            return false;
        }

        if (($conferencia['inventario']['situacao'] ?? null) === 'Concluído') {
            return false;
        }

        return (bool) ($conferencia['atividade']['pode_editar'] ?? false);
    }

    public function podeFinalizarInventario(): bool
    {
        $conferencia = $this->conferencia();

        if ($conferencia === null) {
            return false;
        }

        if (($conferencia['inventario']['situacao'] ?? null) === 'Concluído') {
            return false;
        }

        return (bool) ($conferencia['atividade']['pode_finalizar'] ?? false);
    }

    private function conferencia(): ?array
    {
        if ($this->conferenciaCarregada) {
            return $this->conferencia;
        }

        $this->conferenciaCarregada = true;
        $scope = $this->scope();

        if ($scope === null) {
            return $this->conferencia = null;
        }

        try {
            $this->conferencia = app(ConferenciaBensService::class)->atual($scope);
        } catch (NotFoundHttpException) {
            $this->conferencia = null;
        }

        return $this->conferencia;
    }

    private function resetConferencia(): void
    {
        $this->conferencia = null;
        $this->conferenciaCarregada = false;
    }

    /**
     * @return array{id_egap: int, setor: int, unidade_judiciaria: int}|null
     */
    private function scope(): ?array
    {
        $setor = $this->setorAtual;
        $unidade = SetorSelecionado::resolverUnidadeAtual();
        $idEgap = UserEgap::currentAuthenticated()?->getKey();

        if (blank($setor) || blank($unidade) || blank($idEgap)) {
            return null;
        }

        return [
            'id_egap' => (int) $idEgap,
            'setor' => (int) $setor,
            'unidade_judiciaria' => (int) $unidade,
        ];
    }

    public static function termoPendente(BemMovel $record): ?string
    {
        $transferencia = $record->ultimaTransferencia;
        $termo = $transferencia?->termoRel;
        $arquivoDigital = $termo?->arquivoDigital;

        if (! $termo || (int) ($arquivoDigital?->situacao ?? -1) !== ArquivoDigital::SITUACAO_PENDENTE) {
            return null;
        }

        return "{$termo->num_termo}/{$termo->ano_termo}";
    }

    public static function queryDoSetor(?int $setor): Builder
    {
        return BemMovel::query()
            ->when(
                blank($setor),
                fn(Builder $query) => $query->whereKey(-1),
                fn(Builder $query) => $query->where('Setor', $setor)
            )
            ->whereIn('SituacaoBem', self::SITUACOES_ATIVAS)
            ->with([
                'complementoSetorRef',
                'situacaoBemRef',
                'marcaRef',
                'modeloRef',
                'ultimaTransferencia.termoRel.arquivoDigital',
            ]);
    }

    public function render(): View
    {
        return view('egap.livewire.externo.bens-no-setor-table');
    }
}
