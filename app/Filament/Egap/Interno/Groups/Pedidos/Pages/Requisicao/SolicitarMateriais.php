<?php

namespace App\Filament\Egap\Interno\Groups\Pedidos\Pages\Requisicao;

use App\Filament\Egap\Auth\EgapSession;
use App\Filament\Egap\Interno\Clusters\PedidosCluster;
use App\Filament\Egap\Interno\Permissions\Pedidos\PedidoPage;
use App\Models\egap\Almoxarifado\FasePedido;
use App\Models\egap\Almoxarifado\ItemPedido;
use App\Models\egap\Almoxarifado\Pedidos;
use App\Models\egap\Almoxarifado\SituacaoPedido;
use App\Models\egap\Cadastro\ComplementoSetor;
use App\Models\egap\Cadastro\DescricaoResumida;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\UserEgap;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class SolicitarMateriais extends PedidoPage implements HasForms
{
    use InteractsWithForms;

    protected const STATUS_ATENDIDO = 3;
    protected const STATUS_CANCELADO = 4;
    protected const STATUS_INVALIDADO = 5;
    protected const STATUS_EM_ANALISE = 6;
    protected const STATUS_VALIDADO = 7;
    protected const STATUS_EM_ATENDIMENTO = 9;
    protected const STATUS_CONCLUIDO = 12;

    protected static ?string $cluster = PedidosCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-plus-circle';
    protected static string | \UnitEnum | null $navigationGroup = 'Requisição';
    protected static ?string $title = 'Pedidos - Materiais Permanentes';
    protected static ?string $slug = 'solicitar-materiais';
    protected static ?string $navigationLabel = 'Solicitar Materiais';
    protected string $view = 'egap.filament.pages.pedidos.requisicao.solicitar-materiais';

    public ?array $data = [];

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function mount(): void
    {
        $this->form->fill($this->getDefaultFormState());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Solicitação de materiais permanentes')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Pedido de Materiais Permanentes')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Dados do pedido')
                                    ->description('Informações principais do solicitante, destino e documentação.')
                                    ->icon('heroicon-o-building-office-2')
                                    ->schema([
                                        Grid::make(12)
                                            ->schema([
                                                TextInput::make('num_protocolo')
                                                    ->label('Núm. protocolo')
                                                    ->placeholder('Ex: 2026.01.000.123')
                                                    ->columnSpan(6),

                                                Select::make('Solicitante')
                                                    ->label('Solicitante')
                                                    ->searchable()
                                                    ->preload()
                                                    ->default(fn () => EgapSession::resolverAtual() ?? null)
                                                    ->required()
                                                    ->options(fn(): array => UserEgap::query()
                                                        ->orderBy('name')
                                                        ->pluck('name', 'id')
                                                        ->toArray())
                                                    ->native(false)
                                                    ->columnSpan(6),

                                                Select::make('UnidadeJudiciaria')
                                                    ->label('Unidade judiciária')
                                                    ->required()
                                                    ->searchable()
                                                    ->preload()
                                                    ->live()
                                                    ->options(
                                                        fn() => Setores::query()
                                                            ->whereColumn('id', 'codigodauo')
                                                            ->orderBy('unidadeorganizacional')
                                                            ->pluck('unidadeorganizacional', 'codigopai')
                                                            ->toArray()
                                                    )
                                                    ->afterStateUpdated(fn(Set $set) => $set('Setor', null))
                                                    ->native(false)
                                                    ->columnSpan(4),

                                                Select::make('Setor')
                                                    ->label('Setor')
                                                    ->required()
                                                    ->searchable()
                                                    ->preload()
                                                    ->options(
                                                        fn (Get $get) => blank($get('UnidadeJudiciaria'))
                                                            ? []
                                                            : Setores::query()
                                                                ->where('codigodauo', $get('UnidadeJudiciaria'))
                                                                ->whereColumn('id', '<>', 'codigopai')
                                                                ->orderBy('setor')
                                                                ->pluck('setor', 'id')
                                                                ->toArray()
                                                    )
                                                    ->disabled(fn(Get $get): bool => blank($get('UnidadeJudiciaria')))
                                                    ->native(false)
                                                    ->columnSpan(4),

                                                Select::make('ComplementoSetor')
                                                    ->label('Complemento do setor')
                                                    ->searchable()
                                                    ->preload()
                                                    ->options(fn(): array => ComplementoSetor::query()
                                                        ->orderBy('descricao')
                                                        ->pluck('descricao', 'id')
                                                        ->toArray())
                                                    ->native(false)
                                                    ->columnSpan(4),

                                                Select::make('setor_responsavel')
                                                    ->label('Setor responsável')
                                                    ->searchable()
                                                    ->preload()
                                                    ->options(fn(): array => Setores::query()
                                                        ->whereIn('id', [799, 1239])
                                                        ->orderBy('setor')
                                                        ->pluck('setor', 'id')
                                                        ->toArray())
                                                    ->native(false)
                                                    ->columnSpanFull(),

                                                FileUpload::make('arquivo')
                                                    ->label('Arquivo da requisição')
                                                    ->directory('pedidos')
                                                    ->disk('egap')
                                                    ->visibility('public')
                                                    ->columnSpanFull(),
                                            ]),
                                    ]),

                                Section::make('Contexto do pedido')
                                    ->description('Textos institucionais que serão gravados no pedido.')
                                    ->icon('heroicon-o-chat-bubble-left-right')
                                    ->schema([
                                        Textarea::make('Observacao')
                                            ->label('Observação')
                                            ->rows(4)
                                            ->placeholder('Informações complementares sobre o setor, urgência ou instruções de atendimento.')
                                            ->columnSpanFull(),

                                        Textarea::make('justificativa')
                                            ->label('Justificativa')
                                            ->rows(4)
                                            ->placeholder('Descreva a necessidade da aquisição e o contexto do pedido.')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tab::make('Situação do Pedido')
                            ->icon('heroicon-o-clipboard-document-check')
                            ->schema([
                                Section::make('Situação inicial')
                                    ->description('Dados do fluxo que serão persistidos no pedido assim que ele for criado.')
                                    ->icon('heroicon-o-arrow-path')
                                    ->schema([
                                        Grid::make(12)
                                            ->schema([
                                                DatePicker::make('DataTermino')
                                                    ->label('Previsão / Data de término')
                                                    ->native(false)
                                                    ->displayFormat('d/m/Y')
                                                    ->columnSpan(4),

                                                Select::make('ResponsavelAtendimento')
                                                    ->label('Responsável pelo atendimento')
                                                    ->searchable()
                                                    ->preload()
                                                    ->options(fn(): array => UserEgap::query()
                                                        ->orderBy('name')
                                                        ->pluck('name', 'id')
                                                        ->toArray())
                                                    ->native(false)
                                                    ->columnSpan(4),
                                                Select::make('idSituacao')
                                                    ->label('Situação do pedido')
                                                    ->required()
                                                    ->searchable()
                                                    ->preload()
                                                    ->options(fn(): array => SituacaoPedido::query()
                                                        ->orderBy('Descricao')
                                                        ->pluck('Descricao', 'id')
                                                        ->toArray())
                                                    ->native(false)
                                                    ->default(self::STATUS_EM_ANALISE)
                                                    ->columnSpan(4),
                                            ]),
                                    ]),
                            ]),

                        Tab::make('Materiais do Pedido')
                            ->icon('heroicon-o-cube')
                            ->schema([
                                Section::make('Itens')
                                    ->description('Cada item será adicionado ao pedido final.')
                                    ->icon('heroicon-o-archive-box')
                                    ->schema([
                                        Repeater::make('itens')
                                            ->label('Materiais')
                                            ->defaultItems(1)
                                            ->minItems(1)
                                            ->addActionLabel('Adicionar material')
                                            ->cloneable()
                                            ->collapsible()
                                            ->columns(12)
                                            ->schema([
                                                Select::make('material')
                                                    ->label('Material')
                                                    ->required()
                                                    ->searchable()
                                                    ->preload()
                                                    ->live()
                                                    ->options(fn(): array => DescricaoResumida::query()
                                                        ->where('id_tipo_material', 'P')
                                                        ->orderBy('Descricao')
                                                        ->pluck('Descricao', 'id')
                                                        ->toArray())
                                                    ->native(false)
                                                    ->columnSpanFull(),

                                                TextInput::make('QuantidadeMaterial')
                                                    ->label('Quantidade')
                                                    ->required()
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->live(onBlur: true)
                                                    ->default(1)
                                                    ->columnSpan(4),

                                                TextInput::make('quantidade_validada')
                                                    ->label('Quantidade validada')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->required(fn(Get $get): bool => $this->statusExigeValidacao($get('situacao')))
                                                    ->columnSpan(4),

                                                TextInput::make('QuantidadeMaterialAtendida')
                                                    ->label('Quantidade atendida')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->default(0)
                                                    ->required(fn(Get $get): bool => $this->statusExigeAtendimento($get('situacao')))
                                                    ->columnSpan(4),

                                                Select::make('situacao')
                                                    ->label('Situação do item')
                                                    ->required()
                                                    ->searchable()
                                                    ->preload()
                                                    ->live()
                                                    ->options(fn(): array => SituacaoPedido::query()
                                                        ->distinct()
                                                        ->orderBy('Descricao')
                                                        ->pluck('Descricao', 'id')
                                                        ->toArray())
                                                    ->default(fn(Get $get): int => (int) ($get('../../idSituacao') ?: self::STATUS_EM_ANALISE))
                                                    ->afterStateUpdated(function (Get $get, Set $set, $state): void {
                                                        $this->sincronizarCamposDoItem($get, $set, $state);
                                                    })
                                                    ->native(false)
                                                    ->columnSpan(4),

                                                DatePicker::make('data_validacao')
                                                    ->label('Data de validação')
                                                    ->native(false)
                                                    ->required(fn(Get $get): bool => $this->statusExigeValidacao($get('situacao')))
                                                    ->columnSpan(4),

                                                Select::make('validado_por')
                                                    ->label('Validado por')
                                                    ->searchable()
                                                    ->preload()
                                                    ->native(false)
                                                    ->required(fn(Get $get): bool => $this->statusExigeValidacao($get('situacao')))
                                                    ->options(fn(): array => UserEgap::query()
                                                        ->orderBy('name')
                                                        ->pluck('name', 'id')
                                                        ->toArray())
                                                    ->columnSpan(4),

                                                Textarea::make('ObservacaoItem')
                                                    ->label('Observação')
                                                    ->columnSpan(6),

                                                Textarea::make('justificativa')
                                                    ->label('Justificativa')
                                                    ->columnSpan(6),
                                            ])
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        $data = $this->form->getState();
        $userId = auth()->id();

        if (! $userId) {
            Notification::make()
                ->title('Usuário autenticado não encontrado.')
                ->danger()
                ->send();

            return;
        }

        try {
            $this->validateBusinessRules($data);

            /** @var \App\Filament\Egap\Interno\Groups\Pedidos\Pages\Requisicao\Pedidos $pedido */
            $pedido = DB::connection('egap')->transaction(function () use ($data, $userId) {
                $statusPedido = (int) ($data['idSituacao'] ?? self::STATUS_EM_ANALISE);

                $pedido = Pedidos::query()->create([
                    'date_time' => $data['date_time'] ?? now(),
                    'Solicitante' => $data['Solicitante'] ?? $userId,
                    'UnidadeJudiciaria' => $data['UnidadeJudiciaria'],
                    'Setor' => $data['Setor'],
                    'Observacao' => $data['Observacao'] ?? null,
                    'DataTermino' => $data['DataTermino'] ?? null,
                    'ResponsavelAtendimento' => $data['ResponsavelAtendimento'] ?? null,
                    'idSituacao' => $statusPedido,
                    'num_protocolo' => $data['num_protocolo'] ?? null,
                    'arquivo' => $data['arquivo'] ?? null,
                    'justificativa' => $data['justificativa'] ?? null,
                    'setor_responsavel' => $data['setor_responsavel'] ?? null,
                    'ComplementoSetor' => $data['ComplementoSetor'] ?? null,
                ]);

                $this->registrarFasePedido(
                    pedido: $pedido,
                    statusId: $statusPedido,
                    descricao: $data['fase_descricao'] ?? 'Pedido criado via página de solicitação de materiais.'
                );

                foreach ($data['itens'] ?? [] as $itemData) {
                    $itemPayload = $this->prepareItemPayload($itemData, $statusPedido, $userId);

                    /** @var ItemPedido $item */
                    $item = $pedido->itens()->create($itemPayload);

                    $this->registrarFaseItem(
                        pedido: $pedido,
                        item: $item,
                        statusId: (int) $item->situacao
                    );
                }

                return $pedido;
            });

            Notification::make()
                ->title("Pedido #{$pedido->id} criado com sucesso.")
                ->body('O pedido, os itens e o histórico inicial foram gravados no banco.')
                ->success()
                ->send();

            $this->form->fill($this->getDefaultFormState());
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Erro ao criar pedido.')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getDefaultFormState(): array
    {
        return [
            'date_time' => now(),
            'Solicitante' => auth()->id(),
            'idSituacao' => self::STATUS_EM_ANALISE,
            'fase_descricao' => 'Pedido criado via página de solicitação de materiais.',
            'itens' => [
                [
                    'QuantidadeMaterial' => 1,
                    'quantidade_validada' => null,
                    'QuantidadeMaterialAtendida' => 0,
                    'situacao' => self::STATUS_EM_ANALISE,
                    'data_validacao' => null,
                    'validado_por' => null,
                ],
            ],
        ];
    }

    protected function prepareItemPayload(array $itemData, int $statusPedido, int $userId): array
    {
        $statusItem = (int) ($itemData['situacao'] ?? $statusPedido ?? self::STATUS_EM_ANALISE);
        $quantidadeSolicitada = (int) ($itemData['QuantidadeMaterial'] ?? 0);
        $quantidadeValidada = filled($itemData['quantidade_validada'] ?? null)
            ? (int) $itemData['quantidade_validada']
            : null;
        $quantidadeAtendida = filled($itemData['QuantidadeMaterialAtendida'] ?? null)
            ? (int) $itemData['QuantidadeMaterialAtendida']
            : 0;
        $dataValidacao = $itemData['data_validacao'] ?? null;
        $dataCancelado = $itemData['data_cancelado'] ?? null;
        $validadoPor = $itemData['validado_por'] ?? null;
        $canceladoPor = $itemData['cancelado_por'] ?? null;

        if ($statusItem === self::STATUS_INVALIDADO && $quantidadeValidada === null) {
            $quantidadeValidada = 0;
        }

        if ($this->statusExigeValidacao($statusItem) && $statusItem !== self::STATUS_INVALIDADO && $quantidadeValidada === null) {
            $quantidadeValidada = $quantidadeSolicitada;
        }

        if ($this->statusExigeAtendimento($statusItem) && $quantidadeAtendida === 0) {
            $quantidadeAtendida = $quantidadeValidada ?? $quantidadeSolicitada;
        }

        if (in_array($statusItem, [self::STATUS_INVALIDADO, self::STATUS_CANCELADO], true)) {
            $quantidadeAtendida = 0;
        }

        if ($this->statusExigeValidacao($statusItem) && blank($dataValidacao)) {
            $dataValidacao = now();
            $validadoPor = $validadoPor ?: $userId;
        }

        if ($statusItem === self::STATUS_EM_ANALISE) {
            $quantidadeValidada = null;
            $dataValidacao = null;
            $validadoPor = null;
            $quantidadeAtendida = 0;
        }

        if ($statusItem === self::STATUS_CANCELADO && blank($dataCancelado)) {
            $dataCancelado = now();
            $canceladoPor = $canceladoPor ?: $userId;
        }

        return [
            'date_time' => now(),
            'QuantidadeMaterial' => $quantidadeSolicitada,
            'ObservacaoItem' => $itemData['ObservacaoItem'] ?? null,
            'QuantidadeMaterialAtendida' => $quantidadeAtendida,
            'material' => $itemData['material'],
            'DescricaoDetalhada' => $itemData['DescricaoDetalhada'] ?? null,
            'data_validacao' => $dataValidacao,
            'situacao' => $statusItem,
            'justificativa' => $itemData['justificativa'] ?? null,
            'validado_por' => $validadoPor,
            'data_cancelado' => $dataCancelado,
            'cancelado_por' => $canceladoPor,
            'quantidade_validada' => $quantidadeValidada,
            'valor_material' => $this->normalizeMoney($itemData['valor_material'] ?? null),
        ];
    }

    protected function validateBusinessRules(array $data): void
    {
        $errors = [];

        $unidadeId = (int) ($data['UnidadeJudiciaria'] ?? 0);
        $setorId = (int) ($data['Setor'] ?? 0);
        $setorResponsavelId = filled($data['setor_responsavel'] ?? null)
            ? (int) $data['setor_responsavel']
            : null;

        $unidadeValida = Setores::query()
            ->whereKey($unidadeId)
            ->whereColumn('id', 'CodigoPai')
            ->exists();

        if (! $unidadeValida) {
            $errors['data.UnidadeJudiciaria'] = 'Selecione uma unidade judiciária válida.';
        }

        $setor = Setores::query()
            ->select('id', 'codigopai')
            ->find($setorId);

        if (! $setor || (int) $setor->codigopai !== $unidadeId) {
            $errors['data.Setor'] = 'O setor informado não pertence à unidade judiciária selecionada.';
        }

        if ($setorResponsavelId !== null && ! in_array($setorResponsavelId, [799, 1239], true)) {
            $errors['data.setor_responsavel'] = 'O setor responsável informado não é permitido para esta solicitação.';
        }

        $itens = $data['itens'] ?? [];

        if (! is_array($itens) || $itens === []) {
            $errors['data.itens'] = 'Adicione ao menos um material ao pedido.';
        }

        foreach ($itens as $index => $itemData) {
            $prefix = 'data.itens.' . $index;
            $statusItem = (int) ($itemData['situacao'] ?? $data['idSituacao'] ?? self::STATUS_EM_ANALISE);
            $quantidadeSolicitada = (int) ($itemData['QuantidadeMaterial'] ?? 0);
            $quantidadeValidada = filled($itemData['quantidade_validada'] ?? null)
                ? (int) $itemData['quantidade_validada']
                : null;
            $quantidadeAtendida = filled($itemData['QuantidadeMaterialAtendida'] ?? null)
                ? (int) $itemData['QuantidadeMaterialAtendida']
                : 0;

            if ($quantidadeSolicitada < 1) {
                $errors["{$prefix}.QuantidadeMaterial"] = 'A quantidade solicitada deve ser maior que zero.';
            }

            if ($quantidadeValidada !== null && $quantidadeValidada > $quantidadeSolicitada) {
                $errors["{$prefix}.quantidade_validada"] = 'A quantidade validada não pode ser maior que a quantidade solicitada.';
            }

            if ($this->statusExigeValidacao($statusItem)) {
                if ($quantidadeValidada === null) {
                    $quantidadeValidada = $statusItem === self::STATUS_INVALIDADO ? 0 : $quantidadeSolicitada;
                }

                if ($statusItem === self::STATUS_INVALIDADO && $quantidadeValidada !== 0) {
                    $errors["{$prefix}.quantidade_validada"] = 'Itens invalidados devem permanecer com quantidade validada igual a zero.';
                }

                if ($statusItem !== self::STATUS_INVALIDADO && $quantidadeValidada < 1) {
                    $errors["{$prefix}.quantidade_validada"] = 'A quantidade validada deve ser maior que zero para esse status.';
                }
            }

            $limiteAtendimento = $quantidadeValidada ?? $quantidadeSolicitada;

            if ($quantidadeAtendida > $limiteAtendimento) {
                $errors["{$prefix}.QuantidadeMaterialAtendida"] = 'A quantidade atendida não pode ser maior que a quantidade validada.';
            }

            if (in_array($statusItem, [self::STATUS_CANCELADO, self::STATUS_INVALIDADO], true) && $quantidadeAtendida > 0) {
                $errors["{$prefix}.QuantidadeMaterialAtendida"] = 'Itens cancelados ou invalidados não podem possuir quantidade atendida.';
            }

            if ($quantidadeAtendida > 0 && ! $this->statusPermiteQuantidadeAtendida($statusItem)) {
                $errors["{$prefix}.situacao"] = 'A situação do item não permite quantidade atendida maior que zero.';
            }

            if (in_array($statusItem, [self::STATUS_ATENDIDO, self::STATUS_CONCLUIDO], true) && $quantidadeAtendida !== $limiteAtendimento) {
                $errors["{$prefix}.QuantidadeMaterialAtendida"] = 'Itens atendidos ou concluídos devem estar totalmente atendidos.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    protected function registrarFasePedido(Pedidos $pedido, int $statusId, string $descricao): void
    {
        FasePedido::query()->create([
            'idSituacao' => $statusId,
            'Descricao' => $descricao,
            'id_pedido' => $pedido->id,
        ]);
    }

    protected function registrarFaseItem(Pedidos $pedido, ItemPedido $item, int $statusId): void
    {
        FasePedido::query()->create([
            'idSituacao' => $statusId,
            'Descricao' => 'Item incluído no pedido.',
            'id_pedido' => $pedido->id,
            'id_itempedido' => $item->id,
            'id_descricaoresumida' => $item->material,
            'id_descricaodetalhada' => $item->DescricaoDetalhada,
            'quantidade' => $item->QuantidadeMaterial,
        ]);
    }

    protected function normalizeMoney(float|int|string|null $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = preg_replace('/[^\d,.-]/', '', (string) $value) ?? '0';

        if (str_contains($value, ',')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }

        return (float) $value;
    }

    protected function sincronizarCamposDoItem(Get $get, Set $set, mixed $state): void
    {
        $status = (int) ($state ?: self::STATUS_EM_ANALISE);
        $quantidadeSolicitada = (int) ($get('QuantidadeMaterial') ?? 0);

        if ($status === self::STATUS_EM_ANALISE) {
            $set('quantidade_validada', null);
            $set('QuantidadeMaterialAtendida', 0);
            $set('data_validacao', null);
            $set('validado_por', null);

            return;
        }

        if ($status === self::STATUS_INVALIDADO && blank($get('quantidade_validada'))) {
            $set('quantidade_validada', 0);
        }

        if ($this->statusExigeValidacao($status) && $status !== self::STATUS_INVALIDADO && blank($get('quantidade_validada'))) {
            $set('quantidade_validada', $quantidadeSolicitada);
        }

        if ($this->statusExigeAtendimento($status) && blank($get('QuantidadeMaterialAtendida'))) {
            $set('QuantidadeMaterialAtendida', $get('quantidade_validada') ?: $quantidadeSolicitada);
        }

        if (in_array($status, [self::STATUS_INVALIDADO, self::STATUS_CANCELADO], true)) {
            $set('QuantidadeMaterialAtendida', 0);
        }
    }

    protected function statusExigeValidacao(int|string|null $status): bool
    {
        return in_array((int) $status, [
            self::STATUS_INVALIDADO,
            self::STATUS_VALIDADO,
            self::STATUS_EM_ATENDIMENTO,
            self::STATUS_ATENDIDO,
            self::STATUS_CONCLUIDO,
        ], true);
    }

    protected function statusExigeAtendimento(int|string|null $status): bool
    {
        return in_array((int) $status, [
            self::STATUS_ATENDIDO,
            self::STATUS_CONCLUIDO,
        ], true);
    }

    protected function statusPermiteQuantidadeAtendida(int|string|null $status): bool
    {
        return in_array((int) $status, [
            self::STATUS_EM_ATENDIMENTO,
            self::STATUS_ATENDIDO,
            self::STATUS_CONCLUIDO,
        ], true);
    }
}
