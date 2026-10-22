<?php

namespace App\Filament\Egap\Interno\Groups\Pedidos\Resources;

use App\Filament\Egap\Interno\Clusters\PedidosCluster;
use App\Filament\Egap\Interno\Groups\Pedidos\Resources\ValidarPedidoResource\Pages\ListValidarPedidos;
use App\Filament\Egap\Interno\Permissions\Pedidos\PedidoResource;
use App\Filament\Egap\Support\MoneyInput;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Filament\Egap\Support\TableModalAction;
use App\Mail\Egap\Pedidos\PedidoStatusAlteradoMail;
use App\Models\egap\Almoxarifado\ItemPedido;
use App\Models\egap\Cadastro\DescricaoDetalhada;
use App\Models\egap\Patrimonio\BensImoveis\BemImovel;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use Livewire\Livewire;

class ValidarPedidoResource extends PedidoResource
{
    protected static ?string $model = ItemPedido::class;

    protected static ?string $cluster = PedidosCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-check-badge';

    protected static string | \UnitEnum | null $navigationGroup = 'Requisição';

    protected static ?string $navigationLabel = 'Validar Pedidos';

    protected static ?string $title = 'Itens do Pedido';

    protected static ?string $modelLabel = 'item do pedido';

    protected static ?string $pluralModelLabel = 'itens do pedido';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Item do pedido')
                    ->icon(Heroicon::OutlinedCube)
                    ->compact()
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        Select::make('idPedido')
                            ->label('N° Pedido')
                            ->relationship('idPedidoRef', 'id')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Selecione o pedido'),

                        Select::make('material')
                            ->label('Material')
                            ->relationship('materialRef', 'Descricao')
                            ->required()
                            ->live()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Selecione o material')
                            ->afterStateUpdated(fn(Set $set) => $set('DescricaoDetalhada', null)),

                        Select::make('DescricaoDetalhada')
                            ->label('Descrição detalhada')
                            ->required()
                            ->live()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->disabled(fn(Get $get): bool => blank($get('material')))
                            ->placeholder(fn(Get $get): string => blank($get('material'))
                                ? 'Selecione um material primeiro'
                                : 'Selecione a descrição detalhada')
                            ->options(fn(Get $get): array => DescricaoDetalhada::query()
                                ->where('descricao_resumida', $get('material'))
                                ->pluck('descricao_detalhada', 'id')
                                ->toArray())
                            ->columnSpanFull(),

                        Textarea::make('ObservacaoItem')
                            ->label('Observação')
                            ->placeholder('Observações sobre este item')
                            ->rows(2)
                            ->autosize()
                            ->columnSpanFull(),
                    ]),

                Section::make('Quantidades')
                    ->icon(Heroicon::OutlinedCalculator)
                    ->compact()
                    ->columnSpan(1)
                    ->columns(2)
                    ->schema([
                        TextInput::make('QuantidadeMaterial')
                            ->label('Solicitada')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->placeholder('0'),

                        TextInput::make('quantidade_validada')
                            ->label('Validada')
                            ->numeric()
                            ->minValue(0)
                            ->placeholder('0'),

                        TextInput::make('QuantidadeMaterialAtendida')
                            ->label('Atendida')
                            ->numeric()
                            ->minValue(0)
                            ->placeholder('0'),

                        MoneyInput::make('valor_material')
                            ->label('Valor'),
                    ]),

                Section::make('Validação')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->compact()
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        Select::make('situacao')
                            ->label('Situação')
                            ->relationship('situacaoRef', 'Descricao')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Selecione a situação'),

                        Select::make('validado_por')
                            ->label('Validado por')
                            ->relationship('validadoPorRef', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Selecione o responsável'),

                        DateTimePicker::make('data_validacao')
                            ->label('Data da validação')
                            ->seconds(false)
                            ->native(false)
                            ->displayFormat('d/m/Y H:i'),

                        Textarea::make('justificativa')
                            ->label('Justificativa')
                            ->placeholder('Motivo da validação ou do cancelamento')
                            ->rows(2)
                            ->autosize()
                            ->columnSpanFull(),
                    ]),

                Section::make('Cancelamento')
                    ->description('Preencha apenas se o item foi cancelado.')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->compact()
                    ->collapsed(fn(?ItemPedido $record): bool => blank($record?->data_cancelado))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('cancelado_por')
                            ->label('Cancelado por')
                            ->relationship('canceladoPorRef', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Selecione o responsável'),

                        DateTimePicker::make('data_cancelado')
                            ->label('Data do cancelamento')
                            ->seconds(false)
                            ->native(false)
                            ->displayFormat('d/m/Y H:i'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->defaultSort('date_time', 'DESC')
            ->columns([
                TableColumns::text('idPedido', 'N° Pedido', isFirstColumn: true, isIdColumn: true)
                    ->description(fn($record) => "Solicitante: {$record->idPedidoRef->solicitante_get->name}")
                    ->color('primary'),

                TableColumns::text('descricaoDetalhadaRef.descricao_detalhada', 'Material')
                    ->wrap()
                    ->alignLeft(),

                TableColumns::text('justificativa', 'Justificativa')
                    ->formatStateUsing(fn(?string $state): ?string => ItemPedido::formatarJustificativa($state)),

                TableColumns::number('QuantidadeMaterial', 'Qtde Solicitada'),

                TableColumns::number('quantidade_validada', 'Qtde Validada')
                    ->color('info'),

                TableColumns::number('QuantidadeMaterialAtendida', 'Qtde Atendida')
                    ->color('success'),

                TableColumns::money('valor_material', 'Valor')
                    ->badge()
                    ->color('gray'),

                TableColumns::text('situacaoRef.Descricao', 'Situação')
                    ->badge()
                    ->color(fn(ItemPedido $record): string|array => self::corSituacao($record->situacao)),

                TableColumns::updatedBy('validadoPorRef.name', 'Validado por', 'data_validacao'),

                TableColumns::updatedBy('canceladoPorRef.name', 'Cancelado por', 'data_cancelado'),

                TableColumns::text('ObservacaoItem', 'Observação')
                    ->limit(50),

                TableColumns::text('justificativa', 'Justificativa')
                    ->formatStateUsing(fn(?string $state): ?string => ItemPedido::formatarJustificativa($state))
                    ->limit(50),
            ])
            ->filters([
                Filter::make('no_pedido')
                    ->label('No Pedido')
                    ->schema([
                        TextInput::make('value')
                            ->label('N° Pedido')
                            ->placeholder('Digite o número do pedido'),
                    ])
                    ->query(fn(Builder $query, array $data): Builder => $query
                        ->when(
                            filled($data['value'] ?? null),
                            fn(Builder $builder): Builder => $builder->where('idPedido', 'like', '%' . $data['value'] . '%')
                        )),

                SelectFilter::make('material')
                    ->label('Material')
                    ->relationship('materialRef', 'Descricao')
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('situacao')
                    ->label('Situação Material')
                    ->relationship('situacaoRef', 'Descricao')
                    ->searchable()
                    ->preload()
                    ->native(false),

            ])
            ->recordActions([
                EditAction::make()
                    ->tooltip('Editar')
                    ->modalHeading(' ')
                    ->modalWidth(Width::SixExtraLarge)
                    ->hiddenLabel(),
                ViewAction::make()
                    ->tooltip('Visualizar')
                    ->modalHeading(' ')
                    ->modalWidth(Width::SixExtraLarge)
                    ->hiddenLabel(),
                DeleteAction::make()
                    ->tooltip('Excluir')
                    ->hiddenLabel(),
                ActionGroup::make([
                    self::validarMateriaisAction(),
                    self::invalidarPedidoAction(),
                    self::cancelarMateriaisAction(),
                    self::enviarEmailAction(),
                    self::materiaisSetorAction(),
                    self::suspenderPedidoAction(),
                ])
            ]);
    }

    /**
     * Cor do badge por id de egap_ped_situacao.
     */
    private static function corSituacao(int|string|null $situacao): string|array
    {
        return match ((int) $situacao) {
            4, 5 => 'danger',        // Cancelado, Invalidado
            6, 13 => 'warning',      // Em análise, Em revisão
            7 => 'info',             // Validado
            8, 9 => Color::Indigo,   // Agendado, Em atendimento
            3, 12 => 'success',      // Atendido, Concluido
            11 => Color::Orange,     // Devolvido
            default => 'gray',       // Suspenso, Tácito, sem situação
        };
    }

    private static function validarMateriaisAction(): Action
    {
        return Action::make('validar_materiais')
            ->label('Validar Materiais')
            ->icon(Heroicon::OutlinedCheck)
            ->visible(fn(ItemPedido $record): bool => blank($record->data_validacao) && blank($record->data_cancelado))
            ->requiresConfirmation()
            ->modalHeading(fn(ItemPedido $record): string => "Validar Materiais do Pedido #{$record->idPedido}")
            ->modalDescription('Deseja validar o(s) material(is) selecionado(s)?')
            ->modalSubmitActionLabel('Validar')
            ->action(function (ItemPedido $record): void {
                $record->update([
                    'situacao' => 7,
                    'data_validacao' => now(),
                    'validado_por' => auth()->id(),
                ]);

                Notification::make()
                    ->title('Material validado com sucesso!')
                    ->success()
                    ->send();
            });
    }

    private static function invalidarPedidoAction(): Action
    {
        return Action::make('invalidar_pedido')
            ->label('Invalidar Pedido')
            ->icon(Heroicon::OutlinedXCircle)
            ->visible(fn(ItemPedido $record): bool => (int) $record->situacao !== 5)
            ->requiresConfirmation()
            ->modalHeading(fn(ItemPedido $record): string => "Invalidar Pedido #{$record->idPedido}")
            ->modalDescription('Deseja invalidar este pedido?')
            ->modalSubmitActionLabel('Invalidar')
            ->action(function (ItemPedido $record): void {
                $record->update([
                    'situacao' => 5,
                    'data_validacao' => now(),
                    'validado_por' => auth()->id(),
                ]);

                Notification::make()
                    ->title('Pedido invalidado com sucesso!')
                    ->success()
                    ->send();
            });
    }

    private static function cancelarMateriaisAction(): Action
    {
        return Action::make('cancelar_materiais')
            ->label('Cancelar Materiais')
            ->icon(Heroicon::OutlinedHandRaised)
            ->visible(fn(ItemPedido $record): bool => blank($record->data_cancelado))
            ->requiresConfirmation()
            ->modalHeading(fn(ItemPedido $record): string => "Cancelar Materiais do Pedido #{$record->idPedido}")
            ->modalDescription('Deseja cancelar o(s) material(is) selecionado(s)?')
            ->modalSubmitActionLabel('Cancelar')
            ->action(function (ItemPedido $record): void {
                $record->update([
                    'situacao' => 4,
                    'data_cancelado' => now(),
                    'cancelado_por' => auth()->id(),
                ]);

                Notification::make()
                    ->title('Material cancelado com sucesso!')
                    ->success()
                    ->send();
            });
    }

    private static function enviarEmailAction(): Action
    {
        return Action::make('enviar_email')
            ->label('Enviar Email')
            ->icon(Heroicon::OutlinedEnvelope)
            ->requiresConfirmation()
            ->modalHeading('Enviar Email')
            ->modalDescription(fn(ItemPedido $record): string => "Enviar email ao solicitante ({$record->idPedidoRef?->solicitante_get?->email}) informando o status do pedido #{$record->idPedido}?")
            ->modalSubmitActionLabel('Enviar')
            ->action(function (ItemPedido $record): void {
                $email = $record->idPedidoRef?->solicitante_get?->email;

                if (blank($email)) {
                    Notification::make()
                        ->title('Não foi possível enviar o email.')
                        ->body('O solicitante deste pedido não possui um e-mail cadastrado.')
                        ->danger()
                        ->send();

                    return;
                }

//                Mail::to('lucsantos@tjes.jus.br')->send(new PedidoStatusAlteradoMail($record));
                Mail::to($email)->send(new PedidoStatusAlteradoMail($record));

                Notification::make()
                    ->title('Email enviado com sucesso!')
                    ->success()
                    ->send();
            });
    }

    private static function materiaisSetorAction(): Action
    {
        return TableModalAction::make('materiais_no_setor')
            ->label('Materiais do Setor')
            ->icon(Heroicon::OutlinedCube)
            ->modalHeading(fn(ItemPedido $record): string => "Materiais do Setor - {$record->idPedidoRef->setor_get->setor}")
            ->modalContent(fn(ItemPedido $record): HtmlString => new HtmlString(
                Livewire::mount(
                    'pedidos.materiais-do-setor-modal',
                    ['setorId' => (int) $record->idPedidoRef->setor_get->id],
                    "materiais-do-setor-{$record->getKey()}",
                )
            ));
    }

    private static function suspenderPedidoAction(): Action
    {
        return Action::make('suspender_pedido')
            ->label('Suspender Pedido')
            ->icon(Heroicon::OutlinedBarsArrowUp)
            ->visible(fn(ItemPedido $record): bool => (int) $record->situacao !== 10)
            ->requiresConfirmation()
            ->modalHeading(fn(ItemPedido $record): string => "Suspender Pedido #{$record->idPedido}")
            ->modalDescription('Deseja suspender este pedido?')
            ->modalSubmitActionLabel('Suspender')
            ->action(function (ItemPedido $record): void {
                $record->update([
                    'situacao' => 10,
                    'validado_por' => auth()->id(),
                ]);

                Notification::make()
                    ->title('Pedido suspenso com sucesso!')
                    ->success()
                    ->send();
            });
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'idPedidoRef',
            'idPedidoRef.solicitante_get',
            'idPedidoRef.setor_get',
            'materialRef',
            'descricaoDetalhadaRef',
            'situacaoRef',
            'validadoPorRef',
            'canceladoPorRef',
        ]);
    }
    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListValidarPedidos::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return true;
    }

}
