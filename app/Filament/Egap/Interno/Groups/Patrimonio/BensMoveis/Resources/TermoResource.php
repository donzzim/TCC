<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources;

use App\Filament\Egap\Interno\Clusters\PatrimonioCluster;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\TermoResource\Pages\CreateTermo;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\TermoResource\Pages\EditTermo;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\TermoResource\Pages\ListTermos;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Filament\Egap\Support\TableModalAction;
use App\Helper\CpfHelper;
use App\Models\egap\Almoxarifado\Pedidos;
use App\Models\egap\Patrimonio\BensMoveis\ArquivoDigital;
use App\Models\egap\Patrimonio\BensMoveis\Termo;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Egap\Interno\Permissions\Patrimonio\PatrimonioResource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Livewire\Livewire;
use RuntimeException;
use Throwable;

class TermoResource extends PatrimonioResource
{
    protected static ?string $model = Termo::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $cluster = PatrimonioCluster::class;

    protected static string | \UnitEnum | null $navigationGroup = 'Bens Móveis';

    protected static ?string $navigationLabel = 'Termos de Responsabilidade';

    protected static ?string $modelLabel = 'Termo de Responsabilidade';

    protected static ?string $pluralModelLabel = 'Termos de Responsabilidade';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'bens-moveis/termos';

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Identificação do Termo')
                    ->description('Informe os dados que identificam o termo de responsabilidade.')
                    ->icon('heroicon-o-document-text')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextInput::make('num_termo')
                            ->label('Número do Termo')
                            ->numeric()
                            ->minValue(1)
                            ->required(),

                        TextInput::make('ano_termo')
                            ->label('Ano do Termo')
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue((int) now()->addYear()->format('Y'))
                            ->default((int) now()->format('Y'))
                            ->required(),

                        Select::make('pedido_no')
                            ->label('Número do Pedido')
                            ->placeholder('Pesquise pelo número ou protocolo do pedido')
                            ->relationship(
                                name: 'pedidoRef',
                                titleAttribute: 'id',
                            )
                            ->getOptionLabelFromRecordUsing(function (Pedidos $record): string {
                                $ano = $record->date_time?->format('Y') ?? 'Sem ano';

                                return "{$record->id}/{$ano} - Prot. {$record->num_protocolo}";
                            })
                            ->searchable(['id', 'num_protocolo'])
                            ->optionsLimit(50)
                            ->native(false)
                            ->columnSpanFull()
                            ->required(),
                    ]),

                Section::make('Situação da Entrega')
                    ->description('Acompanha somente o fluxo logístico do termo.')
                    ->icon('heroicon-o-truck')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('situacao_entrega')
                            ->label('Situação da Entrega')
                            ->options([
                                'Reservado' => 'Reservado',
                                'Em rota' => 'Em rota',
                                'Entregue' => 'Entregue',
                                'Encaminhado para Logística' => 'Encaminhado para Logística',
                            ])
                            ->default('Reservado')
                            ->native(false)
                            ->required(),
                    ]),

                Section::make('Anexo do Termo')
                    ->icon('heroicon-o-paper-clip')
                    ->columnSpanFull()
                    ->schema([
                        Group::make([
                            Grid::make(3)
                                ->schema([
                                    FileUpload::make('arquivo_digital')
                                        ->label('Arquivo Digital')
                                        ->helperText(fn(?ArquivoDigital $record): string => filled($record?->arquivo_digital)
                                            ? 'A substituição do PDF deve ser feita em Validar Termos.'
                                            : 'Somente PDF, com tamanho máximo de 10 MB.')
                                        ->disk('egap')
                                        ->directory('images/termos')
                                        ->visibility('public')
                                        ->acceptedFileTypes(['application/pdf'])
                                        ->maxSize(10240)
                                        ->openable()
                                        ->downloadable()
                                        ->previewable(false)
                                        ->disabled(fn(?ArquivoDigital $record): bool => filled($record?->arquivo_digital))
                                        ->columnSpan(2),
                                    Select::make('situacao')
                                        ->label('Situação do Arquivo')
                                        ->options(ArquivoDigital::situacaoOptions())
                                        ->default(ArquivoDigital::SITUACAO_PENDENTE)
                                        ->dehydrated()
                                        ->native(false),
                                    Textarea::make('observacao')
                                        ->label('Observação')
                                        ->rows(3)
                                        ->columnSpan(2),
                                    Toggle::make('web')
                                        ->label('Disponível na WEB')
                                        ->default(false)
                                        ->inline(false),
                                ]),
                        ])
                            ->relationship('arquivoDigital')
                            ->mutateRelationshipDataBeforeFillUsing(function (array $data): array {
                                $data['arquivo_digital'] = ArquivoDigital::caminhoArquivoDigitalNoDisco(
                                    $data['arquivo_digital'] ?? null,
                                );

                                return $data;
                            })
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                $data['situacao'] ??= ArquivoDigital::SITUACAO_PENDENTE;
                                $data['web'] ??= false;

                                return $data;
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->modifyQueryUsing(fn(Builder $query) => $query
                ->with(['arquivoDigital.validadoPor', 'responsavelRef'])
                ->withCount('transferencias'))
            ->columns([
                TableColumns::text('id', '#', isFirstColumn: true, isIdColumn: true),

                TableColumns::text('pedido_no', 'Pedido No'),

                TableColumns::text('situacao_entrega', 'Situação da Entrega')
                    ->badge()
                    ->color(fn(?string $state): string => match ($state) {
                        'Entregue' => 'success',
                        'Em rota' => 'info',
                        'Encaminhado para Logística' => 'warning',
                        default => 'gray',
                    }),

                TableColumns::text('termo_completo', 'Termo')
                    ->searchable(['num_termo', 'ano_termo'])
                    ->weight('medium')
                    ->color('primary')
                    ->url(fn(Termo $record): string => route('termo.imprimir', ['id' => $record->id]))
                    ->openUrlInNewTab(),

                TableColumns::text('arquivoDigital.arquivo_digital', 'Arquivo Digital')
                    ->formatStateUsing(fn(?string $state): string => filled($state) ? basename($state) : '-')
                    ->color('primary')
                    ->limit(50)
                    ->weight('medium')
                    ->url(fn(Termo $record): ?string => $record->arquivoDigital?->url)
                    ->openUrlInNewTab(),

                TableColumns::updatedBy('responsavelRef.name', dateColumn: 'atualizado_em'),

                TableColumns::text('arquivoDigital.validadoPor.name', 'Analisado por'),

                TableColumns::text('arquivoDigital.observacao', 'Observação')
                    ->limit(50)
                    ->tooltip(fn(Termo $record): ?string => $record->arquivoDigital?->observacao)
                    ->wrap(),

                TableColumns::text('arquivoDigital.situacao', 'Situação')
                    ->formatStateUsing(fn($state): string => ArquivoDigital::situacaoLabel($state))
                    ->badge()
                    ->color(fn($state): string => ArquivoDigital::situacaoColor($state)),

                TableColumns::text('arquivoDigital.web', 'WEB')
                    ->formatStateUsing(fn($state): string => match ($state === null ? null : (int) $state) {
                        1 => 'Sim',
                        0 => 'Não',
                        default => '-',
                    })
                    ->badge()
                    ->color(fn($state): string => (int) $state === 1 ? 'success' : 'danger'),

                TableColumns::text('transferencias_count', 'Materiais')
                    ->searchable(false)
                    ->color('primary')
                    ->weight('bold')
                    ->tooltip('Clique para visualizar os materiais deste termo')
                    ->extraAttributes([
                        'class' => 'cursor-pointer underline',
                    ])
                    ->action(self::materiaisTableAction()),
            ])
            ->filters([
                SelectFilter::make('situacao_entrega')
                    ->label('Situação da Entrega')
                    ->options([
                        'Reservado' => 'Reservado',
                        'Em rota' => 'Em rota',
                        'Entregue' => 'Entregue',
                        'Encaminhado para Logística' => 'Encaminhado para Logística',
                    ]),
                Filter::make('num_termo')
                    ->schema([
                        TextInput::make('num_termo')
                            ->label('Termo')
                            ->placeholder('Informe o número do termo'),
                    ])
                    ->query(fn(Builder $query, array $data): Builder => $query
                        ->when(
                            filled($data['num_termo'] ?? null),
                            fn(Builder $query): Builder => $query->where(
                                'num_termo',
                                'like',
                                '%' . trim($data['num_termo']) . '%',
                            ),
                        )),

                SelectFilter::make('situacao_arquivo_filter')
                    ->label('Situação do Arquivo')
                    ->options(ArquivoDigital::situacaoOptions())
                    ->query(function (Builder $query, array $data): Builder {
                        $situacao = $data['value'] ?? null;

                        if ($situacao === null || $situacao === '') {
                            return $query;
                        }

                        return $query->whereHas(
                            'arquivoDigital',
                            fn(Builder $query): Builder => $query->where('situacao', (int) $situacao),
                        );
                    }),

                SelectFilter::make('web')
                    ->label('WEB')
                    ->options([
                        1 => 'Sim',
                        0 => 'Não',
                    ])
                    ->query(fn(Builder $query, array $data): Builder => $query
                        ->when(
                            filled($data['value'] ?? null),
                            fn(Builder $query): Builder => $query->whereHas(
                                'arquivoDigital',
                                fn(Builder $query): Builder => $query->where('web', (int) $data['value']),
                            ),
                        )),
            ])
            ->recordActions([
                ...TableDefaults::actions(),
                ActionGroup::make([
                    self::imprimirTermoTableAction(),
                    self::novoTermoTableAction(),
                    self::gerarNovoArquivoTableAction(),
                    self::corrigirTermoTableAction(),
                    self::encaminharLogisticaTableAction(),
                    self::validarTermosRedirectTableAction(),
                ])
                    ->hiddenLabel()
                    ->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->defaultSort('id', 'desc');
    }

    private static function materiaisTableAction(): Action
    {
        return TableModalAction::make('visualizar_materiais')
            ->modalHeading(fn(Termo $record): string => "Materiais do termo {$record->termo_completo}")
            ->modalContent(fn(Termo $record): HtmlString => new HtmlString(
                Livewire::mount(
                    'patrimonio.materiais-termo-modal',
                    ['termoId' => (int) $record->getKey()],
                    "materiais-termo-{$record->getKey()}",
                )
            ));
    }

    private static function imprimirTermoTableAction(): Action
    {
        return Action::make('imprimir_termo')
            ->label('Imprimir termo')
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->url(fn(Termo $record): string => route('termo.imprimir', ['id' => $record->id]))
            ->openUrlInNewTab();
    }

    private static function validarTermosRedirectTableAction(): Action
    {
        return Action::make('validarTermosRedirect')
            ->label('Validar Termo')
            ->icon('heroicon-o-check-badge')
            ->color('gray')
            ->url(fn(Termo $record): string => ValidarTermoResource::getUrl('index', [
                'tableFilters' => [
                    'termo_filter' => [
                        'termo' => "{$record->num_termo}/{$record->ano_termo}",
                    ],
                ],
            ]));
    }

    private static function encaminharLogisticaTableAction(): Action
    {
        return Action::make('encaminhar_logistica')
            ->label('Encaminhar para Logística')
            ->icon('heroicon-o-arrow-up-on-square')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Encaminhar para Logística')
            ->modalDescription('A situação de entrega será atualizada para Encaminhado para Logística.')
            ->disabled(fn(Termo $record): bool => $record->situacao_entrega === 'Encaminhado para Logística')
            ->action(function (Termo $record): void {
                if ($record->situacao_entrega === 'Encaminhado para Logística') {
                    Notification::make()
                        ->title('O termo já foi encaminhado para Logística.')
                        ->warning()
                        ->send();

                    return;
                }

                $record->update([
                    'situacao_entrega' => 'Encaminhado para Logística',
                ]);

                Notification::make()
                    ->title('Termo encaminhado para Logística.')
                    ->success()
                    ->send();
            });
    }

    private static function novoTermoTableAction(): Action
    {
        return Action::make('novo_termo_corretivo')
            ->label('Criar termo corretivo')
            ->icon('heroicon-o-arrow-path-rounded-square')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Criar termo corretivo')
            ->modalDescription('Cria um novo termo invertendo a origem e o destino de todos os bens deste termo.')
            ->action(function (Termo $record): void {
                try {
                    $userId = auth()->id();

                    if (! $userId) {
                        throw new RuntimeException('Usuário autenticado não identificado.');
                    }

                    $novoTermo = $record->getConnection()->transaction(function () use ($record, $userId): Termo {
                        $transferencias = $record->transferencias()
                            ->lockForUpdate()
                            ->get();

                        if ($transferencias->isEmpty()) {
                            throw new RuntimeException('O termo não possui materiais para movimentar.');
                        }

                        $ano = (int) now()->year;
                        $ultimoTermo = Termo::query()
                            ->where('ano_termo', $ano)
                            ->orderByDesc('num_termo')
                            ->lockForUpdate()
                            ->first(['num_termo']);

                        $novoTermo = Termo::query()->create([
                            'num_termo' => ((int) $ultimoTermo?->num_termo) + 1,
                            'ano_termo' => $ano,
                            'situacao_entrega' => 'Reservado',
                        ]);

                        $novoTermo->arquivoDigital()->create([
                            'situacao' => 0,
                            'web' => 1,
                        ]);

                        $novoTermo->transferencias()->createMany(
                            $transferencias->map(fn($transferencia): array => [
                                'NumPatrimonio' => $transferencia->NumPatrimonio,
                                'UnidadeAtual' => $transferencia->UnidadeAnterior,
                                'SetorAtual' => $transferencia->SetorAnterior,
                                'ComplementoAtual' => $transferencia->ComplementoAnterior,
                                'AndarAtual' => $transferencia->AndarAnterior,
                                'UnidadeAnterior' => $transferencia->UnidadeAtual,
                                'SetorAnterior' => $transferencia->SetorAtual,
                                'ComplementoAnterior' => $transferencia->ComplementoAtual,
                                'AndarAnterior' => $transferencia->AndarAtual,
                                'Usuario' => $userId,
                            ])->all(),
                        );

                        return $novoTermo;
                    });

                    Notification::make()
                        ->title("Termo {$novoTermo->num_termo}/{$novoTermo->ano_termo} criado corretamente.")
                        ->success()
                        ->send();
                } catch (Throwable $exception) {
                    self::sendActionError('Erro ao criar o termo corretivo', $exception);
                }
            });
    }

    private static function gerarNovoArquivoTableAction(): Action
    {
        return Action::make('gerar_novo_arquivo')
            ->label('Gerar novo arquivo')
            ->icon('heroicon-o-document-plus')
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription('Regenera o arquivo HTML do termo com os dados atuais.')
            ->action(fn(Termo $record) => self::storeTermoDocument(
                $record,
                preserveValidation: false,
                successMessage: 'Arquivo gerado corretamente.',
            ))
            ->visible(fn(Termo $record): bool => (int) ($record->arquivoDigital?->situacao ?? 0) !== 1);
    }

    private static function corrigirTermoTableAction(): Action
    {
        return Action::make('corrigir_termo')
            ->label('Corrigir arquivo validado')
            ->icon('heroicon-o-wrench-screwdriver')
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription('Regenera o arquivo do termo validado, mantendo os dados de assinatura e validação.')
            ->action(fn(Termo $record) => self::storeTermoDocument(
                $record,
                preserveValidation: true,
                successMessage: 'Arquivo validado corrigido corretamente.',
            ))
            ->visible(fn(Termo $record): bool => (int) ($record->arquivoDigital?->situacao ?? 0) === 1);
    }

    private static function storeTermoDocument(
        Termo $record,
        bool $preserveValidation,
        string $successMessage,
    ): void {
        $storedPath = null;
        $fileAlreadyExisted = false;

        try {
            if (! auth()->id()) {
                throw new RuntimeException('Usuário autenticado não identificado.');
            }

            $record->getConnection()->transaction(function () use (
                $record,
                $preserveValidation,
                &$storedPath,
                &$fileAlreadyExisted,
            ): void {
                $arquivoDigital = $record->arquivoDigital()
                    ->latest('id')
                    ->lockForUpdate()
                    ->first();

                if ($preserveValidation && (int) ($arquivoDigital?->situacao ?? 0) !== 1) {
                    throw new RuntimeException('O termo não possui um arquivo validado para correção.');
                }

                if (! $preserveValidation && (int) ($arquivoDigital?->situacao ?? 0) === 1) {
                    throw new RuntimeException('O termo já foi validado. Utilize a ação de correção.');
                }

                $arquivoDigital ??= $record->arquivoDigital()->make([
                    'situacao' => 0,
                    'web' => 1,
                ]);

                $html = self::renderTermoDocument($record, $arquivoDigital, $preserveValidation);
                $year = (int) ($record->ano_termo ?: now()->year);
                $directory = "images/termos/{$year}";
                $number = preg_replace('/[^0-9_-]/', '_', "{$record->num_termo}_{$year}");
                $filename = "termo_{$number}_" . md5($html) . '.html';
                $relativePath = "/{$directory}/{$filename}";
                $storedPath = "{$directory}/{$filename}";

                $fileAlreadyExisted = Storage::disk('egap')->exists($storedPath);

                if (Storage::disk('egap')->put($storedPath, $html) === false) {
                    throw new RuntimeException('Não foi possível gravar o arquivo do termo.');
                }

                $arquivoDigital->fill([
                    'arquivo_digital' => $relativePath,
                    'atualizado_em' => now(),
                    'atualizado_por' => auth()->id(),
                ]);

                if ($arquivoDigital->exists) {
                    // O evento saving legado altera date_time, que pode conter a data de validação histórica.
                    $arquivoDigital->saveQuietly();
                } else {
                    $record->arquivoDigital()->save($arquivoDigital);
                }
            });

            Notification::make()->title($successMessage)->success()->send();
        } catch (Throwable $exception) {
            if ($storedPath && ! $fileAlreadyExisted) {
                Storage::disk('egap')->delete($storedPath);
            }

            self::sendActionError('Erro ao gerar o arquivo do termo', $exception);
        }
    }

    private static function renderTermoDocument(
        Termo $record,
        ArquivoDigital $arquivoDigital,
        bool $preserveValidation,
    ): string {
        $record->load([
            'ultimaTransferencia.setorAtualRel',
            'ultimaTransferencia.complementoAtualRel',
            'ultimaTransferencia.usuarioRef.infoUser',
            'transferencias' => fn($query) => $query
                ->select(['id', 'Termo', 'NumPatrimonio'])
                ->orderBy('id'),
            'transferencias.bem' => fn($query) => $query
                ->select([
                    'id',
                    'NumPatrimonio',
                    'Descricao',
                    'Marca',
                    'Modelo',
                    'EstadodeConservacao',
                    'ValorAquisicao',
                    'ValordaReavaliacao',
                    'DatadeIncorporacao',
                ]),
            'transferencias.bem.marcaRef:id,descricao',
            'transferencias.bem.modeloRef:id,descricao',
        ]);

        $ultimaTransferencia = $record->ultimaTransferencia;
        $usuarioEmitente = $ultimaTransferencia?->usuarioRef;
        $infoEmitente = $usuarioEmitente?->infoUser;
        $usuarioAutenticado = auth()->user();
        $usuarioDestinatario = $preserveValidation
            ? $arquivoDigital->validadoPor()->with('infoUser')->first()
            : $usuarioEmitente;
        $infoDestinatario = $usuarioDestinatario?->infoUser;

        $bens = $record->transferencias
            ->map(function ($transferencia) {
                $bem = $transferencia->bem;

                if (! $bem) {
                    return null;
                }

                $bem->setAttribute('marca_desc', $bem->marcaRef?->descricao ?? $bem->marcaRef?->Descricao);
                $bem->setAttribute('modelo_desc', $bem->modeloRef?->descricao);
                $bem->setAttribute(
                    'ValorCalculado',
                    optional($bem->DatadeIncorporacao)->lt('2015-01-01')
                        ? $bem->ValordaReavaliacao
                        : $bem->ValorAquisicao
                );

                return $bem;
            })
            ->filter()
            ->unique('NumPatrimonio')
            ->values();

        if ($bens->isEmpty()) {
            throw new RuntimeException('O termo não possui materiais para gerar o arquivo.');
        }

        if ($preserveValidation && ! $usuarioDestinatario) {
            throw new RuntimeException('O usuário que validou o termo não foi encontrado.');
        }

        $dataEmissao = Carbon::parse($record->date_time ?? now())->format('d/m/Y');
        $dataAssinatura = $preserveValidation
            ? Carbon::parse($arquivoDigital->data_validacao ?? $arquivoDigital->date_time ?? now())->format('d/m/Y')
            : $dataEmissao;

        return view('egap.patrimonio.termo_impresso', [
            'termo' => $record,
            'arquivoDigital' => $arquivoDigital,
            'bens' => $bens,
            'unidade' => $ultimaTransferencia?->setorAtualRel?->unidadeorganizacional,
            'setor' => $ultimaTransferencia?->setorAtualRel?->setor,
            'complemento' => $ultimaTransferencia?->complementoAtualRel?->descricao,
            'usuarioEmitente' => $usuarioEmitente?->name ?? $usuarioAutenticado?->name,
            'cargoEmitente' => $infoEmitente?->cargo ?? $usuarioAutenticado?->cargo,
            'cpfEmitente' => CpfHelper::format($infoEmitente?->cpf ?? $usuarioAutenticado?->cpf),
            'usuarioDestinatario' => $usuarioDestinatario?->name,
            'cargoDestinatario' => $infoDestinatario?->cargo,
            'cpfDestinatario' => CpfHelper::format($infoDestinatario?->cpf),
            'dataEmissao' => $dataEmissao,
            'dataAssinatura' => $dataAssinatura,
            'assinaturaEletronica' => true,
        ])->render();
    }

    private static function sendActionError(string $title, Throwable $exception): void
    {
        report($exception);

        Notification::make()
            ->title($title)
            ->body($exception->getMessage())
            ->danger()
            ->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTermos::route('/'),
            'create' => CreateTermo::route('/create'),
            'edit' => EditTermo::route('/{record}/edit'),
        ];
    }
}
