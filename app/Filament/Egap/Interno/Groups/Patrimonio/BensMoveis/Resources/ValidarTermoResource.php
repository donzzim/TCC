<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources;

use App\Filament\Egap\Interno\Clusters\PatrimonioCluster;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\ValidarTermoResource\Pages\CreateValidarTermo;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\ValidarTermoResource\Pages\EditValidarTermo;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\ValidarTermoResource\Pages\ListValidarTermos;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Patrimonio\BensMoveis\ArquivoDigital;
use App\Models\egap\Patrimonio\BensMoveis\Termo;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Egap\Interno\Permissions\Patrimonio\PatrimonioResource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ValidarTermoResource extends PatrimonioResource
{
    protected static ?string $model = ArquivoDigital::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $cluster = PatrimonioCluster::class;

    protected static ?string $slug = 'bens-moveis/validar-termos';

    protected static string | \UnitEnum | null $navigationGroup = 'Bens Móveis';

    protected static ?string $navigationLabel = 'Validar Termos';

    protected static ?string $modelLabel = 'Validação de Termo';

    protected static ?string $pluralModelLabel = 'Validação de Termos';

    protected static ?int $navigationSort = 6;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Documento do Termo')
                    ->description('Vincule o termo e mantenha o arquivo digital usado na validação.')
                    ->icon('heroicon-o-document-check')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        Select::make('termo')
                            ->label('Termo de Responsabilidade')
                            ->relationship('termoRel', 'num_termo')
                            ->getOptionLabelFromRecordUsing(
                                fn(Termo $record): string => "{$record->num_termo}/{$record->ano_termo} - ID {$record->id}"
                            )
                            ->searchable(['id', 'num_termo', 'ano_termo'])
                            ->optionsLimit(50)
                            ->placeholder('Pesquise pelo número, ano ou ID do termo')
                            ->native(false)
                            ->required()
                            ->disabled(fn(string $operation): bool => $operation === 'edit')
                            ->dehydrated()
                            ->columnSpanFull(),

                        FileUpload::make('arquivo_digital')
                            ->label('Arquivo Digital')
                            ->helperText(fn(string $operation): string => $operation === 'edit'
                                ? 'Use a ação "Upload do Termo" para substituir o PDF e retornar a situação para Pendente.'
                                : 'Envie somente arquivos PDF de até 10 MB.')
                            ->disk('egap')
                            ->directory('images/termos')
                            ->visibility('public')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(10240)
                            ->downloadable()
                            ->openable()
                            ->previewable(false)
                            ->required(fn(string $operation): bool => $operation === 'create')
                            ->disabled(fn(string $operation): bool => $operation === 'edit')
                            ->columnSpanFull(),

                        Textarea::make('observacao')
                            ->label('Observação')
                            ->placeholder('Registre informações relevantes sobre o envio ou a validação do documento.')
                            ->rows(5)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ]),

                Section::make('Validação')
                    ->description('Situação atual do arquivo digital.')
                    ->icon('heroicon-o-shield-check')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('situacao')
                            ->label('Situação')
                            ->options(ArquivoDigital::situacaoOptions())
                            ->default(ArquivoDigital::SITUACAO_PENDENTE)
                            ->native(false)
                            ->required()
                            ->dehydrated(),

                        Toggle::make('web')
                            ->label('Disponível na WEB')
                            ->default(false)
                            ->inline(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->modifyQueryUsing(fn(Builder $query): Builder => $query->with([
                'termoRel',
                'atualizadoPor',
                'validadoPor',
            ]))
            ->columns([
                TableColumns::text('id', '#', isFirstColumn: true, isIdColumn: true),

                TableColumns::text('termoRel.num_termo', 'Termo')
                    ->formatStateUsing(fn($state, ArquivoDigital $record): string => $record->termoRel
                        ? "{$record->termoRel->num_termo}/{$record->termoRel->ano_termo}"
                        : '-')
                    ->badge()
                    ->tooltip('Clique para acessar o termo')
                    ->color('primary')
                    ->url(fn(ArquivoDigital $record): ?string => $record->termo
                        ? route('termo.imprimir', ['id' => $record->termo])
                        : null)
                    ->openUrlInNewTab(),

                TableColumns::text('arquivo_digital', 'Arquivo Digital')
                    ->formatStateUsing(fn(ArquivoDigital $record): string => $record->arquivo_digital ? 'Arquivo' : '-')
                    ->iconPosition(IconPosition::After)
                    ->icon(fn(ArquivoDigital $record) => $record->arquivo_digital ? 'heroicon-o-clipboard' : false)
                    ->color('primary')
                    ->limit(50)
                    ->weight('medium')
                    ->url(fn(ArquivoDigital $record): ?string => $record->url)
                    ->openUrlInNewTab(),

                TableColumns::updatedBy('atualizadoPor.name', dateColumn: 'atualizado_em'),

                TableColumns::text('observacao', 'Observação')
                    ->limit(80)
                    ->tooltip(fn($record): ?string => $record->observacao)
                    ->wrap(),

                TableColumns::text('situacao', 'Situação')
                    ->formatStateUsing(fn($state): string => ArquivoDigital::situacaoLabel($state))
                    ->badge()
                    ->color(fn($state): string => ArquivoDigital::situacaoColor($state)),

                TableColumns::text('web', 'WEB')
                    ->formatStateUsing(fn($state): string => match ($state === null ? null : (int) $state) {
                        1 => 'Sim',
                        0 => 'Não',
                        default => '-',
                    })
                    ->badge()
                    ->color(fn($state): string => (int) $state === 1 ? 'success' : 'danger'),
            ])
            ->filters([
                Filter::make('termo_filter')
                    ->schema([
                        TextInput::make('termo')
                            ->label('Termo')
                            ->placeholder('Informe número do termo'),
                    ])
                    ->query(fn(Builder $query, array $data): Builder => self::aplicarFiltroTermo(
                        $query,
                        $data['termo'] ?? null,
                    )),

                SelectFilter::make('situacao')
                    ->label('Situação')
                    ->options(ArquivoDigital::situacaoOptions())
                    ->native(false),

                SelectFilter::make('web')
                    ->label('WEB')
                    ->options([
                        1 => 'Sim',
                        0 => 'Não',
                    ])
                    ->native(false),
            ])
            ->recordActions([
                ...TableDefaults::actions(),
                ActionGroup::make([
                    self::uploadTermoTableAction(),
                    self::validarTermoTableAction(),
                    self::invalidarTermoTableAction(),
                ])
                    ->hiddenLabel()
                    ->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->toolbarActions([
                BulkAction::make('validar_termos_em_lote')
                    ->label('Validar')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        $validados = 0;
                        $userid = (int) auth()->id();

                        foreach ($records as $record) {
                            if ($record instanceof ArquivoDigital && $record->validar($userid)) {
                                $validados++;
                            }
                        }

                        if ($validados === 0) {
                            Notification::make()->title('Nenhum termo selecionado tinha arquivo digital e bens associados.')->warning()->send();

                            return;
                        }

                        Notification::make()
                            ->title($validados === 1 ? '1 termo validado com sucesso!' : "{$validados} termos validados com sucesso!")
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('atualizado_em', 'desc');
    }

    private static function aplicarFiltroTermo(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        [$numero, $ano] = array_pad(preg_split('/\s*\/\s*/', $search, 2), 2, null);

        return $query->whereHas('termoRel', function (Builder $query) use ($search, $numero, $ano): void {
            if (filled($ano)) {
                $query
                    ->where('num_termo', 'like', '%' . $numero . '%')
                    ->where('ano_termo', 'like', '%' . $ano . '%');

                return;
            }

            $query->where(function (Builder $query) use ($search): void {
                $query->where('num_termo', 'like', '%' . $search . '%');

                if (ctype_digit($search)) {
                    $query->orWhere('id', (int) $search);
                }
            });
        });
    }

    private static function uploadTermoTableAction(): Action
    {
        return Action::make('upload_termo')
            ->label('Upload do Termo')
            ->icon('heroicon-o-document-arrow-up')
            ->color('gray')
            ->schema([
                FileUpload::make('arquivo')
                    ->label('Selecione o Termo em PDF')
                    ->required()
                    ->acceptedFileTypes(['application/pdf'])
                    ->disk('egap')
                    ->directory('images/termos')
                    ->maxSize(10240)
                    ->getUploadedFileNameForStorageUsing(
                        fn(ArquivoDigital $record): string => 'termo_' . $record->termo . '_' . date('YmdHis') . '.pdf'
                    ),
            ])
            ->action(function (ArquivoDigital $record, array $data): void {
                $observacao = 'Arquivo Digital anexado. <br />Aguardando validação do Setor de Patrimônio.';

                $record->getConnection()->transaction(function () use ($record, $data, $observacao): void {
                    $record->fill([
                        'arquivo_digital' => $data['arquivo'],
                        'situacao' => ArquivoDigital::SITUACAO_PENDENTE,
                        'observacao' => $observacao,
                        'validado_por' => null,
                        'data_validacao' => null,
                    ])->save();
                });

                Notification::make()->title('Arquivo anexado! Aguardando validação.')->success()->send();
            });
    }

    private static function invalidarTermoTableAction(): Action
    {
        return Action::make('invalidar_termo')
            ->label('Invalidar/Cancelar Termo')
            ->icon('heroicon-o-hand-thumb-down')
            ->color('gray')
            ->schema([
                Select::make('situacao')
                    ->label('Situação')
                    ->options([
                        ArquivoDigital::SITUACAO_INVALIDADO => 'Invalidado',
                        ArquivoDigital::SITUACAO_CANCELADO => 'Cancelado',
                    ])
                    ->native(false)
                    ->required(),
                Textarea::make('observacao')
                    ->label('Observação')
                    ->required(),
            ])
            ->action(function (ArquivoDigital $record, array $data): void {
                $record->getConnection()->transaction(function () use ($record, $data): void {
                    $record->fill([
                        'data_validacao' => now(),
                        'validado_por' => auth()->id(),
                        'situacao' => (int) $data['situacao'],
                        'observacao' => $data['observacao'],
                    ])->save();
                });

                Notification::make()
                    ->title(ArquivoDigital::situacaoLabel($data['situacao']) . ' com sucesso.')
                    ->danger()
                    ->send();
            })
            ->visible(fn(ArquivoDigital $record): bool => $record->situacao !== ArquivoDigital::SITUACAO_CANCELADO);
    }

    private static function validarTermoTableAction(): Action
    {
        return Action::make('validar_termo_novo')
            ->label('Validar Termo')
            ->icon('heroicon-o-hand-thumb-up')
            ->color('gray')
            ->requiresConfirmation()
            ->action(function (ArquivoDigital $record): void {
                $validado = $record->validar((int) auth()->id());

                if (! $validado) {
                    Notification::make()
                        ->title('Não foi possível validar o termo.')
                        ->body('Verifique se existe um termo relacionado e transferencias associadas.')
                        ->warning()
                        ->send();

                    return;
                }

                Notification::make()->title('Termo Validado e Patrimônios Atualizados!')->success()->send();
            })
            ->visible(fn(ArquivoDigital $record): bool => $record->situacao === ArquivoDigital::SITUACAO_PENDENTE);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListValidarTermos::route('/'),
            'create' => CreateValidarTermo::route('/create'),
            'edit' => EditValidarTermo::route('/{record}/edit'),
        ];
    }
}
