<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources;

use App\Filament\Egap\Interno\Clusters\PatrimonioCluster;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\ItemInventarioResource\Pages\CreateItemInventario;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\ItemInventarioResource\Pages\EditItemInventario;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\ItemInventarioResource\Pages\ListItemInventarios;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Cadastro\DescricaoResumida;
use App\Models\egap\Cadastro\Marcas;
use App\Models\egap\Cadastro\Modelos;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use App\Models\egap\Patrimonio\BensMoveis\ItemInventario;
use App\Models\egap\Patrimonio\BensMoveis\TransferenciaBemMovel;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Egap\Interno\Permissions\Patrimonio\PatrimonioResource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Throwable;

class ItemInventarioResource extends PatrimonioResource
{
    protected static ?string $model = ItemInventario::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $cluster = PatrimonioCluster::class;

    protected static string | \UnitEnum | null $navigationGroup = 'Bens Móveis';

    protected static ?string $navigationLabel = 'Itens Inventariados';

    protected static ?string $pluralModelLabel = 'Itens Inventariados';

    protected static ?string $modelLabel = 'Item Inventariado';

    protected static ?int $navigationSort = 15;

    protected static ?string $slug = 'bens-moveis/itens-inventariados';

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Itens Inventariados')
                    ->description('Registre a identificação, localização e conferência física do material.')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('id_inventario')
                                ->label('Inventário'),

                            TextInput::make('id_bem')
                                ->label('Material'),

                            TextInput::make('unidade_localizado')
                                ->label('Unidade Localizado'),

                            Select::make('unidades')
                                ->label('Unidades')
                                ->options(fn() => Setores::pluck('setor', 'id'))
                                ->searchable()
                                ->preload()
                                ->native(false),

                            TextInput::make('setor')
                                ->label('Setor'),

                            TextInput::make('setor_localizado')
                                ->label('Setor Localizado'),

                            TextInput::make('id_complementosetor')
                                ->label('Complemento Setor'),

                            TextInput::make('complemento_localizado')
                                ->label('Complemento Localizado'),

                            TextInput::make('num_patrimonio')
                                ->label('Patrimônio Nº'),

                            TextInput::make('num_patrimonioantigo')
                                ->label('Patrimônio (sem cód. barras)'),

                            TextInput::make('num_serie')
                                ->label('Nº de Série'),

                            TextInput::make('estado_conservacao')
                                ->label('Estado de Conservação'),

                            TextInput::make('descricao_resumida')
                                ->label('Descrição Resumida')
                                ->columnSpan(1),

                            TextInput::make('marca')
                                ->label('Marca'),

                            TextInput::make('modelo')
                                ->label('Modelo'),
                        ]),

                        Grid::make(3)->schema([
                            Textarea::make('descricao_detalhada')
                                ->label('Descrição Detalhada')
                                ->rows(3)
                                ->columnSpan(2),
                            Textarea::make('observacao')
                                ->label('Observação')
                                ->rows(3),
                            TextInput::make('situacao')
                                ->label('Situação'),
                        ]),

                        Section::make('Dados e-GAP (Controle)')
                            ->description('Campos técnicos de integração e auditoria.')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->collapsed()
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('atualizado_por')->label('Atualizado por'),
                                    TextInput::make('num_serie_egap')->label('Num Serie eGAP'),
                                    TextInput::make('descricao_detalhada_egap')->label('Descricao Detalhada eGAP'),

                                    TextInput::make('marca_egap')->label('Marca eGAP'),
                                    TextInput::make('modelo_egap')->label('Modelo eGAP'),
                                    TextInput::make('termo')->label('Termo'),

                                    DatePicker::make('transferido_em')->label('Transferido em')->displayFormat('d/m/Y')->native(false),
                                    TextInput::make('conciliado_patrimonio')->label('Conciliado (Patrimônio)'),
                                    TextInput::make('imagem_enviada')->label('Imagem Enviada'),
                                ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->modifyQueryUsing(fn($query) => $query->with([
                'idBemRef',
                'idInventarioRef',
                'unidadesRef',
            ]))
            ->columns([
                TableColumns::text('id', '#', isFirstColumn: true, isIdColumn: true),
                TableColumns::text('id_inventario', 'Inventário')
                    ->formatStateUsing(fn($state, ItemInventario $record): string => $record->idInventarioRef
                        ? "{$record->idInventarioRef->num_inventario}/{$record->idInventarioRef->ano_inventario}"
                        : (string) ($state ?? '-')),
                TableColumns::text('id_bem', 'Bem')
                    ->badge()
                    ->color(fn($state): string => $state ? 'success' : 'warning')
                    ->formatStateUsing(fn($state, ItemInventario $record): string => $record->idBemRef?->NumPatrimonio
                        ? "{$record->idBemRef->NumPatrimonio} (#{$record->id_bem})"
                        : (string) ($state ?? '-')),
                TableColumns::text('unidades', 'Unidade')
                    ->formatStateUsing(fn($state, ItemInventario $record): string => $record->unidadesRef?->rotuloInventario()
                        ?? (string) ($state ?? '-'))
                    ->limit(35)
                    ->tooltip(fn(ItemInventario $record): ?string => $record->unidadesRef?->rotuloInventario()),
                TableColumns::text('num_patrimonio', 'Patrimônio')
                    ->badge(),
                TableColumns::text('num_patrimonioantigo', 'Patrimônio Antigo'),
                TableColumns::text('num_serie', 'Nº Série'),
                TableColumns::text('descricao_resumida', 'Descrição Resumida')
                    ->limit(40)
                    ->tooltip(fn(ItemInventario $record): ?string => $record->descricao_resumida),
                TableColumns::text('marca', 'Marca'),
                TableColumns::text('modelo', 'Modelo'),
                TableColumns::text('setor', 'Setor'),
                TableColumns::text('estado_conservacao', 'Estado')
                    ->badge()
                    ->color(fn(?string $state): string => match ($state) {
                        'ÓTIMO', 'BOM' => 'success',
                        'REGULAR' => 'warning',
                        'RUIM', 'SUCATA' => 'danger',
                        default => 'gray',
                    }),
                TableColumns::text('setor_localizado', 'Setor Localizado'),
                TableColumns::text('unidade_localizado', 'Unidade Localizado'),
                TableColumns::text('id_complementosetor', 'Complemento Setor', isIdColumn: true),
                TableColumns::text('complemento_localizado', 'Complemento Localizado'),
                TableColumns::text('descricao_detalhada', 'Descrição Detalhada')
                    ->limit(50)
                    ->tooltip(fn(ItemInventario $record): ?string => $record->descricao_detalhada),
                TableColumns::text('observacao', 'Observação')
                    ->limit(50)
                    ->tooltip(fn(ItemInventario $record): ?string => $record->observacao),
                TableColumns::text('situacao', 'Situação')
                    ->badge()
                    ->color(fn(?string $state): string => match (strtoupper(trim((string) $state))) {
                        'LOCALIZADO' => 'success',
                        'AJUSTES' => 'warning',
                        'CONCILIADO' => 'info',
                        'NOVO' => 'primary',
                        'NÃO LOCALIZADO', 'NAO LOCALIZADO' => 'danger',
                        default => 'gray',
                    }),
                TableColumns::text('termo', 'Termo'),
                TableColumns::updatedBy('atualizado_por'),
                TableColumns::text('num_serie_egap', 'Nº Série eGAP'),
                TableColumns::text('descricao_detalhada_egap', 'Descrição Detalhada eGAP')
                    ->limit(50)
                    ->tooltip(fn(ItemInventario $record): ?string => $record->descricao_detalhada_egap),
                TableColumns::text('marca_egap', 'Marca eGAP'),
                TableColumns::text('modelo_egap', 'Modelo eGAP'),
                TableColumns::dateTime('transferido_em', 'Transferido em')
                    ->badge()
                    ->color(fn($state): string => $state ? 'success' : 'warning'),
                TableColumns::text('conciliado_patrimonio', 'Conciliado Patrimônio'),
                TableColumns::text('imagem_enviada', 'Imagem Enviada'),
            ])
            ->filters([
                SelectFilter::make('id_inventario')
                    ->label('Inventário')
                    ->relationship('idInventarioRef', 'num_inventario')
                    ->getOptionLabelFromRecordUsing(fn($record): string => "{$record->num_inventario}/{$record->ano_inventario}")
                    ->searchable()
                    ->preload(),
                SelectFilter::make('unidades')
                    ->label('Unidade')
                    ->relationship('unidadesRef', 'UnidadeOrganizacional')
                    ->getOptionLabelFromRecordUsing(fn(Setores $record): string => $record->rotuloInventario())
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ...TableDefaults::actions(),
                ActionGroup::make([
                    self::finalizarLevantamentoTableAction(),
                    self::localizarItemTableAction(),
                ])
                    ->hiddenLabel()
                    ->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->defaultSort('id', 'desc');
    }

    private static function finalizarLevantamentoTableAction(): Action
    {
        return Action::make('finalizar_levantamento')
            ->hiddenLabel()
            ->tooltip('Finalizar levantamento')
            ->icon('heroicon-o-check-circle')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Finalizar levantamento')
            ->modalDescription('As informações deste item inventariado serão aplicadas ao patrimônio e a transferência será registrada.')
            ->action(function (ItemInventario $record): void {
                try {
                    $resultado = self::finalizarLevantamento($record);
                } catch (Throwable $exception) {
                    Notification::make()
                        ->title('Não foi possível finalizar o levantamento.')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                $notification = Notification::make()
                    ->title($resultado['finalizado']
                        ? 'Levantamento finalizado com sucesso.'
                        : 'Levantamento não finalizado.')
                    ->body($resultado['mensagem']);

                $resultado['finalizado']
                    ? $notification->success()
                    : $notification->warning();

                $notification->send();
            });
    }

    private static function localizarItemTableAction(): Action
    {
        return Action::make('localizar_item')
            ->hiddenLabel()
            ->tooltip('Localizar item')
            ->icon('heroicon-o-map-pin')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Localizar item')
            ->modalDescription('O item inventariado será vinculado ao bem móvel correspondente ao número de patrimônio.')
            ->action(function (ItemInventario $record): void {
                $resultado = self::localizarItemInventario($record);

                if (! $resultado['localizado']) {
                    Notification::make()
                        ->title('Item não localizado.')
                        ->body($resultado['mensagem'])
                        ->warning()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Item localizado com sucesso.')
                    ->body($resultado['mensagem'])
                    ->success()
                    ->send();
            });
    }

    private static function localizarItemInventario(ItemInventario $item): array
    {
        $numeroPatrimonio = trim((string) $item->num_patrimonio);

        if ($numeroPatrimonio === '') {
            return [
                'localizado' => false,
                'mensagem' => 'O item inventariado não possui número de patrimônio informado.',
            ];
        }

        $bem = self::buscarBemPorNumeroPatrimonio($item, $numeroPatrimonio);

        if (! $bem) {
            return [
                'localizado' => false,
                'mensagem' => "Nenhum bem móvel foi encontrado para o patrimônio {$numeroPatrimonio}.",
            ];
        }

        $item->forceFill([
            'id_bem' => $bem->getKey(),
        ])->save();

        $item->refresh();

        return [
            'localizado' => true,
            'mensagem' => "Item vinculado ao bem móvel de patrimônio {$bem->NumPatrimonio}.",
        ];
    }

    private static function finalizarLevantamento(ItemInventario $item): array
    {
        if ((string) $item->unidades === '') {
            return [
                'finalizado' => false,
                'mensagem' => 'O item inventariado não possui unidade informada.',
            ];
        }

        $connectionName = $item->getConnectionName();

        $resultado = DB::connection($connectionName)->transaction(function () use ($item): array {
            $item->refresh();

            $status = self::normalizarStatus($item->situacao);
            $bem = self::bemDoItem($item);

            if ($bem) {
                self::vincularItemAoBem($item, $bem);
            }

            $patrimoniosCriados = 0;
            $patrimoniosAtualizados = 0;
            $transferenciasRegistradas = 0;
            $itensMarcados = 0;
            // o local de origem é lido antes de atualizarBemInventariado() mover o bem
            $localAnterior = $bem
                ? ['UnidadeAnterior' => $bem->UnidadeJudiciaria, 'SetorAnterior' => $bem->Setor]
                : [];

            if ($status === 'NOVO') {
                [$bem, $criado] = self::criarOuReaproveitarBemNovo($item);
                $patrimoniosCriados = $criado ? 1 : 0;
            } elseif (in_array($status, ['LOCALIZADO', 'AJUSTES', 'CONCILIADO'], true) && $bem) {
                $patrimoniosAtualizados = self::atualizarBemInventariado($bem, $item, $status) ? 1 : 0;
            }

            if ($bem && ! self::itemJaTransferido($item)) {
                self::registrarTransferenciaInventario($bem, $item, $status, $localAnterior);
                $transferenciasRegistradas = 1;
            }

            if (! self::itemJaTransferido($item)) {
                $item->forceFill(['transferido_em' => now()]);
                $item->saveQuietly();
                $itensMarcados = 1;
            }

            return compact(
                'patrimoniosCriados',
                'patrimoniosAtualizados',
                'transferenciasRegistradas',
                'itensMarcados',
            );
        });

        $item->refresh();

        $totalAlteracoes = array_sum($resultado);

        return [
            'finalizado' => $totalAlteracoes > 0,
            'mensagem' => sprintf(
                'Patrimônios criados: %d. Patrimônios atualizados: %d. Transferências registradas: %d. Itens marcados como transferidos: %d.',
                $resultado['patrimoniosCriados'],
                $resultado['patrimoniosAtualizados'],
                $resultado['transferenciasRegistradas'],
                $resultado['itensMarcados'],
            ),
        ];
    }

    private static function buscarBemPorNumeroPatrimonio(ItemInventario $item, string $numeroPatrimonio): ?BemMovel
    {
        return BemMovel::on($item->getConnectionName())
            ->where('NumPatrimonio', (int) $numeroPatrimonio)
            ->first();
    }

    private static function bemDoItem(ItemInventario $item): ?BemMovel
    {
        if ((string) $item->id_bem !== '') {
            $bem = BemMovel::on($item->getConnectionName())
                ->whereKey($item->id_bem)
                ->first();

            if ($bem) {
                return $bem;
            }
        }

        $numeroPatrimonio = trim((string) $item->num_patrimonio);

        return $numeroPatrimonio === ''
            ? null
            : self::buscarBemPorNumeroPatrimonio($item, $numeroPatrimonio);
    }

    private static function criarOuReaproveitarBemNovo(ItemInventario $item): array
    {
        $bem = self::bemDoItem($item);

        if ($bem) {
            self::vincularItemAoBem($item, $bem);

            return [$bem, false];
        }

        $bem = new BemMovel();
        $bem->setConnection($item->getConnectionName());
        $bem->forceFill([
            'date_time' => now(),
            'NumPatrimonio' => $item->num_patrimonio,
            'NumerodePatAnterior' => $item->num_patrimonioantigo,
            'NumerodeSerie' => $item->num_serie,
            'UnidadeJudiciaria' => $item->unidades,
            'Setor' => $item->setor,
            'ComplementoSetor' => $item->id_complementosetor,
            'Usuario' => $item->atualizado_por,
            'EstadodeConservacao' => trim((string) $item->estado_conservacao),
            'DescricaoResumidadoBem' => self::descricaoResumidaId($item),
            'Descricao' => $item->descricao_detalhada,
            'Marca' => self::marcaId($item),
            'Modelo' => self::modeloId($item),
            'Observacao' => ' Atualizado pela Comissão de Inventário ',
            'grupo' => 'Inventariado pela Comissão',
            'SituacaoBem' => 8,
            'acuracia' => 'Localizado',
        ]);
        $bem->saveQuietly();

        self::vincularItemAoBem($item, $bem);

        return [$bem, true];
    }

    private static function atualizarBemInventariado(BemMovel $bem, ItemInventario $item, string $status): bool
    {
        if (! in_array((int) $bem->SituacaoBem, [1, 7, 8], true)) {
            return false;
        }

        $attributes = [
            'date_time' => now(),
            'UnidadeJudiciaria' => $item->unidades,
            'Setor' => $item->setor,
            'ComplementoSetor' => $item->id_complementosetor,
            'Usuario' => $item->atualizado_por,
            'EstadodeConservacao' => trim((string) $item->estado_conservacao),
            'Observacao' => self::appendObservacao($bem->Observacao, ' Atualizado pela Comissão de Inventário'),
            'grupo' => 'Inventariado pela Comissão',
            'acuracia' => $status === 'CONCILIADO' ? 'Conciliado' : 'Localizado',
        ];

        if (trim((string) $item->num_patrimonioantigo) !== '') {
            $attributes['NumerodePatAnterior'] = $item->num_patrimonioantigo;
        }

        if (trim((string) $item->num_serie) !== '') {
            $attributes['NumerodeSerie'] = $item->num_serie;
        }

        if (trim((string) $item->descricao_detalhada) !== '') {
            $attributes['Descricao'] = $item->descricao_detalhada;
        }

        if (trim((string) $item->marca) !== '') {
            $attributes['Marca'] = self::marcaId($item);
        }

        if (trim((string) $item->modelo) !== '') {
            $attributes['Modelo'] = self::modeloId($item);
        }

        $bem->forceFill($attributes);
        $bem->saveQuietly();

        return true;
    }

    /**
     * @param  array{UnidadeAnterior?: mixed, SetorAnterior?: mixed}  $localAnterior  local do bem antes do levantamento
     */
    private static function registrarTransferenciaInventario(BemMovel $bem, ItemInventario $item, string $status, array $localAnterior): void
    {
        $transferencia = new TransferenciaBemMovel();
        $transferencia->setConnection($item->getConnectionName());

        $attributes = [
            'date_time' => now(),
            'NumPatrimonio' => $bem->getKey(),
            'UnidadeAtual' => $item->unidades,
            'SetorAtual' => $item->setor,
            'ComplementoAtual' => $item->id_complementosetor,
            'Usuario' => $item->atualizado_por,
            'Termo' => $item->termo,
        ];

        if ($status !== 'NOVO') {
            $attributes += $localAnterior;
        }

        $transferencia->forceFill($attributes);
        $transferencia->saveQuietly();
    }

    private static function vincularItemAoBem(ItemInventario $item, BemMovel $bem): void
    {
        if ((int) $item->id_bem === (int) $bem->getKey()) {
            return;
        }

        $item->forceFill(['id_bem' => $bem->getKey()]);
        $item->saveQuietly();
    }

    private static function descricaoResumidaId(ItemInventario $item): ?int
    {
        $descricao = trim((string) $item->descricao_resumida);

        return $descricao === ''
            ? null
            : DescricaoResumida::on($item->getConnectionName())->where('Descricao', $descricao)->value('id');
    }

    private static function marcaId(ItemInventario $item): ?int
    {
        $marca = trim((string) $item->marca);

        return $marca === ''
            ? null
            : Marcas::on($item->getConnectionName())->where('descricao', $marca)->value('id');
    }

    private static function modeloId(ItemInventario $item): ?int
    {
        $modelo = trim((string) $item->modelo);

        return $modelo === ''
            ? null
            : Modelos::on($item->getConnectionName())->where('descricao', $modelo)->value('id');
    }

    private static function itemJaTransferido(ItemInventario $item): bool
    {
        $transferidoEm = $item->getRawOriginal('transferido_em');

        return $transferidoEm !== null
            && $transferidoEm !== ''
            && $transferidoEm !== '0000-00-00 00:00:00';
    }

    private static function normalizarStatus(?string $status): string
    {
        return strtoupper(trim((string) $status));
    }

    private static function appendObservacao(?string $observacao, string $complemento): string
    {
        return rtrim((string) $observacao) . $complemento;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListItemInventarios::route('/'),
            'create' => CreateItemInventario::route('/create'),
            'edit' => EditItemInventario::route('/{record}/edit'),
        ];
    }
}
