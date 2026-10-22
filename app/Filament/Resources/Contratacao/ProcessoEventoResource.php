<?php

namespace App\Filament\Resources\Contratacao;

use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use App\Filament\Clusters\Contratacao\ProcessoCluster;
use App\Filament\Resources\Contratacao\ProcessoEventoResource\Pages;
use App\Filament\Resources\ProcessoEventoResource\RelationManagers;
use App\Models\Contratacao\Orcamento;
use App\Models\Contratacao\ProcessoEvento;
use App\Models\Cadastro\Tabelas;
use App\Models\Contratacao\Processo;
use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\Width;
use Filament\Support\Exceptions\Halt;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Livewire\Component as Livewire;

class ProcessoEventoResource extends Resource
{
    protected static ?string $model = ProcessoEvento::class;
    protected static ?string $cluster = ProcessoCluster::class;
    protected static null|SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?string $recordTitleAttribute = 'descricao';
    protected static ?string $modelLabel = 'Processo Evento';
    protected static ?string $puralModelLabel = 'Processo Eventos ';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Processo Eventos';
    protected static ?int $navigationSort = 2;

    public static function criarSolicitacaoReserva(array $data)
    {
        try {
            DB::beginTransaction();
            $naturezas = $data['natureza_despesa'];
            foreach ($naturezas as $natureza) {
                $reserva = new ProcessoEvento();
                $reserva->processo_id = $data['processo_id'];
                $reserva->tipoevento_id = config('settings.processoevento.reserva');
                $reserva->data = $data['data'];
                $reserva->numero = $data['numero'];
                $reserva->descricao = $data['descricao'];
                if ($natureza['processoevento_id']) {
                    $reserva->processoevento_id = $natureza['processoevento_id'];
                }

                $reserva->orcamento_id = $natureza['orcamento_id'];
                $orcamento = Orcamento::find($natureza['orcamento_id']);
                $natureza_id = $orcamento->natureza->id;
                $reserva->natureza_id = $natureza_id;

                $reserva->valor = $natureza['valor'];
                $reserva->atualizado_por = Auth::user()->id;
                $reserva->save();

                $orcamento->saldo_1instancia -= (float) $natureza['valor'];
                $orcamento->saldo_2instancia -= (float) $natureza['valor'];
                $orcamento->saldo_total -= (float) $natureza['valor'];
                $orcamento->atualizado_por = Auth::user()->id;
                $orcamento->save();
            }

            DB::commit();

            Notification::make()
                ->title('Solicitação de reserva cadastrada corretamente!')
                ->success()
                ->send();
        } catch (Halt $e) {
            Notification::make()
                ->title('ERRO!' . $e)
                ->danger()
                ->send();
        }
    }
    public static function criarSolicitacaoEmpenho(array $data)
    {
        DB::beginTransaction();
        try {
            // dd($data);
            $i = 0;
            $itens = $data['itens'];
            foreach ($itens as $item) {
                $processoevento_id = $data['processoevento_id'][$i];
                $reserva = ProcessoEvento::find($processoevento_id);

                $empenho = new ProcessoEvento();
                $empenho->tipoevento_id = config('settings.processoevento.empenho');
                $empenho->processo_id = $reserva['processo_id'];
                $empenho->orcamento_id = $reserva['orcamento_id'];
                $empenho->processoevento_id = $processoevento_id;
                $empenho->unidadegestora_id = $data['unidadegestora_id'];
                $empenho->programatrabalho_id = $data['programatrabalho_id'];

                $empenho->data = $data['data'];
                $empenho->numero = $data['numero'];

                $empenho->natureza_id = $item['natureza_id'];
                $empenho->instancia = $item['instancia'];
                $empenho->descricao = $item['descricao'];
                $empenho->quantidade = $item['quantidade'];
                $empenho->valor = $item['valor'];
                $empenho->numero_documento = $item['numero_documento'] ?? null;
                $empenho->data_nota = $item['data_nota'] ?? null;


                $empenho->atualizado_por = Auth::user()->id;
                $empenho->save();
                $i++;
            }


            Notification::make()
                ->title('Solicitação de empenho cadastrada corretamente!')
                ->success()
                ->send();
            DB::commit();
        } catch (Halt $e) {
            DB::rollBack();

            Notification::make()
                ->title('ERRO!' . $e)
                ->danger()
                ->send();
        }
    }
    public static function criarSolicitacaoLiquidacao(array $data)
    {
        try {
            $processoevento_id = $data['processoevento_id'];
            $empenho = ProcessoEvento::find($processoevento_id);

            $liquidacao = new ProcessoEvento();
            $liquidacao->processo_id = $empenho['processo_id'];
            $liquidacao->orcamento_id = $empenho['orcamento_id'];

            $natureza_id = Orcamento::find($empenho['orcamento_id'])->natureza()->id;
            $liquidacao->natureza_id = $natureza_id;

            $liquidacao->processoevento_id = $processoevento_id;
            $liquidacao->numero = $empenho['numero'];
            $liquidacao->valor = $empenho['valor'];
            $liquidacao->tipoevento_id = config('settings.processoevento.liquidacao');
            $liquidacao->data = $data['data'];
            $liquidacao->descricao = $data['descricao'];
            $liquidacao->atualizado_por = Auth::user()->id;
            $liquidacao->save();

            Notification::make()
                ->title('Solicitação de liquidação cadastrada corretamente!')
                ->success()
                ->send();
        } catch (Halt $e) {
            Notification::make()
                ->title('ERRO!' . $e)
                ->danger()
                ->send();
        }
    }
    public static function criarSolicitacaoOrdemServico(array $data)
    {
        try {
            $processoevento_id = $data['processoevento_id'];
            $empenho = ProcessoEvento::find($processoevento_id);

            $liquidacao = new ProcessoEvento();
            $liquidacao->processo_id = $empenho['processo_id'];
            $liquidacao->orcamento_id = $empenho['orcamento_id'];

            $natureza_id = Orcamento::find($empenho['orcamento_id'])->natureza()->id;
            $liquidacao->natureza_id = $natureza_id;

            $liquidacao->processoevento_id = $processoevento_id;
            $liquidacao->numero = $empenho['numero'];
            $liquidacao->valor = $empenho['valor'];
            $liquidacao->tipoevento_id = config('settings.processoevento.liquidacao');
            $liquidacao->data = $data['data'];
            $liquidacao->descricao = $data['descricao'];
            $liquidacao->atualizado_por = Auth::user()->id;
            $liquidacao->save();

            Notification::make()
                ->title('Solicitação de liquidação cadastrada corretamente!')
                ->success()
                ->send();
        } catch (Halt $e) {
            Notification::make()
                ->title('ERRO!' . $e)
                ->danger()
                ->send();
        }
    }
    public static function getFormfieldsReserva(): array
    {
        return [
            Grid::make()->columns(5)->schema([
                Forms\Components\Select::make('processo_id')
                    ->label('Processo')
                    ->options(
                        Processo::all()->pluck('numero', 'id')
                    )
                    ->searchable(['numero', 'descricao'])
                    ->preload()
                    ->live()
                    ->required()
                    ->default(
                        request()->get('processo_id')
                    )
                    ->createOptionForm(
                        ProcessoResource::getFormFields()
                    )
                    ->columnSpan(3),

                Forms\Components\TextInput::make('numero')
                    ->label('Reserva No')
                    ->required()
                    ->columnSpan(1),

                Forms\Components\DatePicker::make('data')
                    ->label('Data')
                    ->required()
                    ->columnSpan(1),

                Forms\Components\Repeater::make('natureza_despesa')
                    ->label('Natureza')
                    ->schema([
                        Grid::make()
                            ->columns(4)
                            ->schema([
                                Forms\Components\Select::make('processoevento_id')
                                    ->label('Reforço Reserva')
                                    // ->options(function (): array {
                                    //     $reservas = ProcessoEvento::where('tipoevento_id', config('settings.processoevento.reserva'))
                                    //         ->where('processo_id', '=', )
                                    //         ->get();
                                    //     $result = [];
                                    //     foreach ($reservas as $reserva) {
                                    //         $result[$reserva->id] = "Reserva: " . $reserva->numero . " " . strip_tags($reserva->descricao);
                                    //     }
                                    //     return $result;
                                    // })
                                    ->searchable()
                                    ->getSearchResultsUsing(function (string $search): array {
                                        return ProcessoEvento::where('tipoevento_id', config('settings.processoevento.reserva'))
                                            // ->where('processo_id', '=', )
                                            ->limit(10)
                                            ->pluck('numero', 'id')
                                            ->toArray();
                                    })
                                    ->getOptionLabelUsing(fn($value): ?string => ProcessoEvento::find($value)?->numero)
                                    ->preload()
                                    // ->required()
                                    ->columnSpan(1),

                                Forms\Components\Select::make('orcamento_id')
                                    ->label('Orçamento')
                                    ->options(function (): array {
                                        $ano = date('Y');
                                        $orcamentos = Orcamento::where('ano', $ano)->get();
                                        $result = [];
                                        foreach ($orcamentos as $orcamento) {
                                            $result[$orcamento->id] = $orcamento->natureza->codigo . ": " . $orcamento->descricao;
                                        }
                                        return $result;
                                    })
                                    ->searchable(['descricao'])
                                    // ->preload()
                                    ->columnSpan(3)
                                    ->createOptionForm(
                                        OrcamentoResource::getFormFields()
                                    ),

                                Forms\Components\TextInput::make('valor')
                                    ->label('Valor')
                                    ->prefix('R$')
                                    ->required()
                                    ->columnSpan(1)
                                    ->numeric()
                                    ->default(0.00)
                                    ->inputMode('decimal'),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->columns(8),

                Forms\Components\RichEditor::make('descricao')
                    ->label('Descrição')
                    ->columnSpanFull(),
            ]),
        ];
    }

    public static function getFormfieldsEmpenho(): array
    {
        return [
            Grid::make()->columns(4)->schema([
                Forms\Components\TextInput::make('numero')
                    ->label('Número')
                    ->required()
                    ->columnSpan(1),

                Forms\Components\DatePicker::make('data')
                    ->label('Data')
                    ->required()
                    ->columnSpan(1),

                Forms\Components\Select::make('unidadegestora_id')
                    ->label('Unidade Gestora')
                    ->options(
                        Tabelas::where('grupo', '=', 'UNIDADE_GESTORA')->pluck('codigo', 'id'),
                    )
                    ->preload()
                    ->required()
                    ->createOptionForm(
                        ProcessoEventoResource::getFormFieldsReserva()
                    )
                    ->columnSpan(1),

                Forms\Components\Select::make('programatrabalho_id')
                    ->label('Programa de trabalho')
                    ->options(
                        Tabelas::where('grupo', '=', 'PROGRAMA_TRABALHO')->pluck('codigo', 'id'),
                    )
                    ->preload()
                    ->required()
                    ->createOptionForm(
                        ProcessoEventoResource::getFormFieldsReserva()
                    )
                    ->columnSpan(1),

                Forms\Components\Select::make('processoevento_id')
                    ->label('Reserva')
                    ->multiple()
                    ->options(function (): array {
                        $reservas = ProcessoEvento::where('tipoevento_id', config('settings.processoevento.reserva'))
                            // ->whereNotNull('numero')
                            ->get();
                        $result = [];
                        foreach ($reservas as $reserva) {
                            $result[$reserva->id] = "Notas de Reserva: " . $reserva->numero . " " . strip_tags($reserva->descricao);
                        }
                        return $result;
                    })
                    ->searchable(['descricao'])
                    ->preload()
                    ->required()
                    ->createOptionForm(
                        ProcessoEventoResource::getFormFieldsReserva()
                    )
                    ->columnSpan(4),

                Forms\Components\Repeater::make('itens')
                    ->columns(8)
                    ->columnSpan(4)
                    ->schema([
                        Forms\Components\Select::make('natureza_id')
                            ->label('Subitem da Despesa')
                            ->options(
                                Tabelas::where('grupo', '=', 'NATUREZA_DESPESA')->pluck('codigo', 'id'),
                            )
                            ->preload()
                            ->required()
                            ->createOptionForm(
                                ProcessoEventoResource::getFormFieldsReserva()
                            )
                            ->columnSpan(2),

                        Forms\Components\Select::make('instancia')
                            ->label('Instância')
                            ->required()
                            ->options([
                                1 => '1ª Instância',
                                2 => '2ª Instância',
                            ])
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('descricao')
                            ->label('Descrição')
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('quantidade')
                            ->label('Quantidade')
                            ->required()
                            ->columnSpan(1)
                            ->numeric()
                            ->default(0.00)
                            ->inputMode('decimal'),

                        Forms\Components\TextInput::make('valor')
                            ->label('Valor')
                            ->prefix('R$')
                            ->required()
                            ->columnSpan(2)
                            ->numeric()
                            ->default(0.00)
                            ->inputMode('decimal'),

                        Forms\Components\TextInput::make('numero_documento')
                            ->label('Nota')
                            ->helperText('Nota de empenho')
                            ->columnSpan(3),

                        Forms\Components\DatePicker::make('data_nota')
                            ->label('Data da Nota')
                            ->columnSpan(2),
                    ]),
            ]),
        ];
    }
    public static function getFormfieldsLiquidacao(): array
    {
        return [
            Grid::make()->columns(4)->schema([
                Forms\Components\Select::make('processoevento_id')
                    ->label('Empenho')
                    ->options(function (): array {
                        $empenhos = ProcessoEvento::where('tipoevento_id', config('settings.processoevento.empenho'))
                            ->whereNotNull('numero')
                            ->get();
                        $result = [];
                        foreach ($empenhos as $empenho) {
                            $result[$empenho->id] = "Notas de empenho: " . $empenho->numero . " " . $empenho->numero_documento;
                        }
                        return $result;
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm(
                        ProcessoEventoResource::getFormFieldsEmpenho()
                    )
                    ->columnSpanFull(),

                Forms\Components\DatePicker::make('data')
                    ->label('Data')
                    ->required()
                    ->columnSpan(1),

                Forms\Components\RichEditor::make('descricao')
                    ->label('Descrição')
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('valor')
                    ->label('Valor')
                    ->prefix('R$')
                    ->required()
                    ->columnSpan(1)
                    ->numeric()
                    ->default(0.00)
                    ->inputMode('decimal'),
            ]),
        ];
    }
    public static function getFormfieldsOrdemServico(): array
    {
        return [
            Grid::make()->columns(4)->schema([
                Forms\Components\Select::make('processoevento_id')
                    ->label('Empenho')
                    ->options(function (): array {
                        $empenhos = ProcessoEvento::where('tipoevento_id', 18)
                            ->whereNotNull('numero')
                            ->get();
                        $result = [];
                        foreach ($empenhos as $empenho) {
                            $result[$empenho->id] = "Notas de empenho: " . $empenho->numero . " " . strip_tags($empenho->descricao);
                        }
                        return $result;
                    })
                    ->searchable(['descricao'])
                    ->preload()
                    ->required()
                    ->createOptionForm(
                        ProcessoEventoResource::getFormFieldsEmpenho()
                    )
                    ->columnSpanFull(),

                Forms\Components\DatePicker::make('data')
                    ->label('Data')
                    ->required()
                    ->columnSpan(1),

                Forms\Components\RichEditor::make('descricao')
                    ->label('Descrição')
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('valor')
                    ->label('Valor')
                    ->prefix('R$')
                    ->required()
                    ->columnSpan(1)
                    ->numeric()
                    ->default(0.00)
                    ->inputMode('decimal'),
            ]),
        ];
    }

    public static function criarSolicitacaoCotacao(array $data): void
    {
        try {
            $empenho = ProcessoEvento::findOrFail($data['processoevento_id']);

            $cotacao = new ProcessoEvento();
            $cotacao->processo_id = $data['processo_id'] ?? $empenho->processo_id;
            $cotacao->orcamento_id = $empenho->orcamento_id;
            $cotacao->natureza_id = $empenho->natureza_id;
            $cotacao->processoevento_id = $empenho->id;
            $cotacao->numero = $empenho->numero;
            $cotacao->valor = $empenho->valor;
            $cotacao->tipoevento_id = config('settings.processoevento.cotacao');
            $cotacao->data = $data['data'];
            $cotacao->descricao = $data['descricao'] ?? null;
            $cotacao->pessoa_id = $data['pessoa_id'];
            $cotacao->atualizado_por = Auth::id();
            $cotacao->save();

            Notification::make()
                ->title('Cotação cadastrada corretamente!')
                ->success()
                ->send();
        } catch (\Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Erro ao cadastrar cotação')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function criarSolicitacaoPassagens(array $data): void
    {
        try {
            $empenho = ProcessoEvento::findOrFail($data['processoevento_id']);

            $passagem = new ProcessoEvento();
            $passagem->processo_id = $data['processo_id'] ?? $empenho->processo_id;
            $passagem->orcamento_id = $empenho->orcamento_id;
            $passagem->natureza_id = $empenho->natureza_id;
            $passagem->processoevento_id = $empenho->id;
            $passagem->numero = $empenho->numero;
            $passagem->valor = $empenho->valor;
            $passagem->tipoevento_id = config('settings.processoevento.passagens');
            $passagem->data = $data['data'];
            $passagem->descricao = $data['descricao'] ?? null;
            $passagem->pessoa_id = $data['pessoa_id'];
            $passagem->atualizado_por = Auth::id();
            $passagem->save();

            Notification::make()
                ->title('Passagem aérea cadastrada corretamente!')
                ->success()
                ->send();
        } catch (\Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Erro ao cadastrar passagem')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function getFormfieldsCotacao(): array
    {
        return [
            Grid::make()->columns(4)->schema([
                Forms\Components\Select::make('processo_id')
                    ->label('Processo')
                    ->options(Processo::query()->pluck('numero', 'id'))
                    ->searchable(['numero', 'descricao'])
                    ->preload()
                    ->required()
                    ->default(request()->get('processo_id'))
                    ->columnSpan(3),

                Forms\Components\Select::make('processoevento_id')
                    ->label('Empenho')
                    ->options(fn (): array => ProcessoEvento::query()
                        ->where('tipoevento_id', config('settings.processoevento.empenho'))
                        ->whereNotNull('numero')
                        ->get()
                        ->mapWithKeys(fn (ProcessoEvento $empenho): array => [
                            $empenho->id => "Nota de empenho: {$empenho->numero} " . strip_tags((string) $empenho->descricao),
                        ])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpan(3),

                Forms\Components\DatePicker::make('data')
                    ->label('Data')
                    ->required()
                    ->columnSpan(1),

                Forms\Components\RichEditor::make('descricao')
                    ->label('Descrição')
                    ->columnSpanFull(),

                Forms\Components\Select::make('pessoa_id')
                    ->label('Pessoa responsável')
                    ->options(User::query()->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->columnSpan(3),

                Forms\Components\TextInput::make('valor')
                    ->label('Valor')
                    ->prefix('R$')
                    ->required()
                    ->numeric()
                    ->default(0.00)
                    ->inputMode('decimal')
                    ->columnSpan(1),
            ]),
        ];
    }

    public static function getFormfieldsPassagens(): array
    {
        return [
            Grid::make()->columns(4)->schema([
                Forms\Components\Select::make('processo_id')
                    ->label('Processo')
                    ->options(Processo::query()->pluck('numero', 'id'))
                    ->searchable(['numero', 'descricao'])
                    ->preload()
                    ->required()
                    ->default(request()->get('processo_id'))
                    ->columnSpan(3),

                Forms\Components\Select::make('processoevento_id')
                    ->label('Empenho')
                    ->options(fn (): array => ProcessoEvento::query()
                        ->where('tipoevento_id', config('settings.processoevento.empenho'))
                        ->whereNotNull('numero')
                        ->get()
                        ->mapWithKeys(fn (ProcessoEvento $empenho): array => [
                            $empenho->id => "Nota de empenho: {$empenho->numero} " . strip_tags((string) $empenho->descricao),
                        ])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpan(3),

                Forms\Components\DatePicker::make('data')
                    ->label('Data')
                    ->required()
                    ->columnSpan(1),

                Forms\Components\RichEditor::make('descricao')
                    ->label('Descrição')
                    ->columnSpanFull(),

                Forms\Components\Select::make('pessoa_id')
                    ->label('Pessoa responsável')
                    ->options(User::query()->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->columnSpan(3),

                Forms\Components\TextInput::make('valor')
                    ->label('Valor')
                    ->prefix('R$')
                    ->required()
                    ->numeric()
                    ->default(0.00)
                    ->inputMode('decimal')
                    ->columnSpan(1),
            ]),
        ];
    }

    public static function getHeaderActions()
    {
        return [
            Action::make('reserva')
                ->label('Reserva')
                ->color('warning')
                ->size('sm')
                ->icon('heroicon-o-document-magnifying-glass')
                ->tooltip('Solicitação de Reserva')
                ->form(
                    ProcessoEventoResource::getFormfieldsReserva(),
                )
                ->action(function (array $data): void {
                    ProcessoEventoResource::criarSolicitacaoReserva($data);
                })
                ->slideOver(),

            Action::make('empenho')
                ->label('Empenho')
                ->tooltip('Solicitação de Empenho')
                ->color('primary')
                ->size('sm')
                ->icon('heroicon-o-document-check')
                ->form(
                    ProcessoEventoResource::getFormfieldsEmpenho(),
                )
                ->action(function (array $data): void {
                    ProcessoEventoResource::criarSolicitacaoEmpenho($data);
                })
                ->slideOver(),

            Action::make('liquidacao')
                ->label('Liquidação')
                ->tooltip('Solicitação de Liquidação')
                ->color('success')
                ->size('sm')
                ->icon('heroicon-o-document-currency-dollar')
                ->form(
                    ProcessoEventoResource::getFormfieldsLiquidacao(),
                )
                ->action(function (array $data): void {
                    ProcessoEventoResource::criarSolicitacaoLiquidacao($data);
                })
                ->slideOver(),

            Action::make('ordemservico')
                ->label('OS/Fornecimento')
                ->tooltip('Ordem de Serviço/Fornecimento')
                ->color('info')
                ->size('sm')
                ->icon('heroicon-o-document-currency-dollar')
                ->form(
                    ProcessoEventoResource::getFormfieldsOrdemServico(),
                )
                ->action(function (array $data): void {
                    ProcessoEventoResource::criarSolicitacaoOrdemServico($data);
                })
                ->slideOver(),

            Action::make('cotacao')
                ->label('Cotação')
                ->tooltip('Cotação de preço')
                ->color('info')
                ->size('sm')
                ->icon('heroicon-o-document-currency-dollar')
                ->form(self::getFormfieldsCotacao())
                ->action(function (array $data): void {
                    self::criarSolicitacaoCotacao($data);
                })
                ->slideOver(),

            Action::make('passagens')
                ->label('Passagens')
                ->tooltip('Passagens aéreas')
                ->color('success')
                ->size('sm')
                ->icon('heroicon-o-paper-airplane')
                ->form(self::getFormfieldsPassagens())
                ->action(function (array $data): void {
                    self::criarSolicitacaoPassagens($data);
                })
                ->slideOver(),
        ];
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(6)
            ->schema(
                [
                    Section::make('Processo')
                        ->columns(2)
                        ->columnSpan(4)
                        ->schema([
                            Forms\Components\Select::make('processo_id')
                                ->label('Processo')
                                ->relationship(
                                    name: 'processo',
                                    titleAttribute: 'numero',
                                )
                                ->searchable(['numero', 'descricao'])
                                ->preload()
                                ->required()
                                ->columnSpan(2),

                            Forms\Components\Select::make('orcamento_id')
                                ->label('Orçamento')
                                ->relationship(
                                    name: 'orcamento',
                                    titleAttribute: 'descricao',
                                )
                                ->live()
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpan(2)
                                ->afterStateUpdated(function (Livewire $livewire) {
                                    $livewire->reset('data.natureza_id');
                                }),

                            Forms\Components\Select::make('natureza_id')
                                ->label('Natureza')
                                ->options(function (Get $get) {
                                    $orcamento = Orcamento::where('id', '=', $get('orcamento_id'))->get();
                                    $rows = [];
                                    foreach ($orcamento as $row) {
                                        $rows[$row->natureza->id] = "{$row->natureza->codigo}: {$row->natureza->descricao}";
                                    }
                                    return $rows;
                                })
                                ->preload()
                                ->required()
                                ->columnSpan(2),

                            Forms\Components\Select::make('microregiao_id')
                                ->inlineLabel()
                                ->label('Microregião')
                                ->options(
                                    Tabelas::where('grupo', '=', 'MICRO_REGIAO')->get()->pluck('descricao', 'id')
                                )
                                ->preload()
                                ->columnSpan(2),

                            Forms\Components\Select::make('unidadegestora_id')
                                ->inlineLabel()
                                ->label('Unidade Gestora')
                                ->options(
                                    Tabelas::where('grupo', '=', 'UNIDADE_GESTORA')->get()->pluck('descricao', 'id')
                                )
                                ->preload()
                                ->columnSpan(2),

                            Forms\Components\Select::make('programatrabalho_id')
                                ->inlineLabel()
                                ->label('Programa Trabalho')
                                ->options(
                                    Tabelas::where('grupo', '=', 'PROGRAMA_TRABALHO')->get()->pluck('descricao', 'id')
                                )
                                ->preload()
                                ->columnSpan(2),

                            Forms\Components\Radio::make('instancia')
                                ->label('InstÃ¢ncia')
                                ->required()
                                ->options([
                                    1 => '1Âª InstÃ¢ncia',
                                    2 => '2Âª InstÃ¢ncia',
                                ])
                                ->inline()
                                ->inlineLabel(false)
                                ->columnSpan(1),
                        ]),

                    Grid::make()
                        ->columnSpan(2)
                        ->schema([
                            Section::make('Evento')
                                ->columns(3)
                                ->columnSpan(2)
                                ->schema([
                                    Forms\Components\Select::make('tipoevento_id')
                                        ->label('Tipo')
                                        ->relationship(
                                            name: 'tipoevento',
                                            titleAttribute: 'descricao',
                                        )
                                        ->preload()
                                        ->required()
                                        ->columnSpan(2),

                                    Forms\Components\DatePicker::make('data')
                                        ->label('Data')
                                        ->required()
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('numero')
                                        ->label('Número ')
                                        ->hint('Doc. SEI')
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('quantidade')
                                        ->label('Quantidade')
                                        ->numeric()
                                        ->default(0.00)
                                        ->inputMode('decimal')
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('valor')
                                        ->label('Valor')
                                        ->prefix('R$')
                                        ->numeric()
                                        ->default(0.00)
                                        ->inputMode('decimal')
                                        ->columnSpan(1),
                                ]),

                            Section::make('Notas')
                                ->description('Notas Registradas no Financeiro')
                                ->columns(1)
                                ->columnSpan(2)
                                ->schema([
                                    Forms\Components\TextInput::make('numero_documento')
                                        ->inlineLabel()
                                        ->label('Nota')
                                        ->helperText('Nota de Reserva, Empenho, Liquidação')
                                        ->columnSpan(1),

                                    Forms\Components\DatePicker::make('data_nota')
                                        ->inlineLabel()
                                        ->label('Data da Nota')
                                        ->columnSpan(1),
                                ]),
                        ]),

                    Forms\Components\RichEditor::make('descricao')
                        ->label('Descrição')
                        ->columnSpanFull(),
                ]
            );
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('processo.numero')
                    ->label("Processo")
                    ->searchable()
                    ->sortable()
                    ->description(fn(ProcessoEvento $record) => $record->tipoevento->descricao),

                // Tables\Columns\TextColumn::make('tipoevento.descricao')
                //     ->label('Evento')
                //     ->badge()
                //     ->color(function (ProcessoEvento $record): string {
                //         $result = 'warning';
                //         if ($record->tipoevento_id == config('settings.processoevento.empenho')) {
                //             $result = 'primary';
                //         } else if ($record->tipoevento_id == config('settings.processoevento.liquidacao')) {
                //             $result = 'success';
                //         }
                //         return $result;
                //     }),

                Tables\Columns\TextColumn::make('orcamento.descricao')
                    ->label('Orçamento')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->html()
                    ->description(fn(ProcessoEvento $record): HtmlString => new HtmlString(
                        $record->orcamento->natureza->codigo . ': ' .
                            $record->orcamento->natureza->descricao
                    )),

                Tables\Columns\TextColumn::make('valor')
                    ->badge()
                    ->numeric()
                    ->money('BRL')
                    ->label('Valor'),

                // Tables\Columns\TextColumn::make('quantidade')
                //     ->label('Quantidade'),

                // Tables\Columns\TextColumn::make('numero')
                //     ->label('Número')
                //     ->prefix('No.'),

                Tables\Columns\TextInputColumn::make('numero_documento')
                    ->label(new HtmlString("Nota de Reserva<br/>Empenho/Liquidação"))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('descricao')
                    ->label('Descrição')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->html(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('processo_id')
                    ->label('Processo')
                    ->options(Processo::all()->pluck('numero', 'id'))
                    ->searchable()
                    ->preload()
                    ->default(request()->get('processo_id'))
                    ->columnSpan(6),
                Tables\Filters\SelectFilter::make('tipoevento_id')
                    ->label('Tipo')
                    ->relationship(
                        name: 'tipoevento',
                        titleAttribute: 'descricao',
                    )
                    ->searchable(['descricao'])
                    ->preload()
                    ->columnSpan(6),

            ], layout: FiltersLayout::Modal)
            ->filtersFormColumns(6)
            ->filtersFormWidth('2xl')
            ->filtersTriggerAction(
                fn(Action $action) => $action
                    ->button()
                    ->label('Filtrar'),
            )
            ->deferFilters()
            ->selectable()
            ->headerActions(
                ProcessoEventoResource::getHeaderActions()
            )
            ->recordActions([
                EditAction::make()
                    ->hiddenLabel()
                    ->tooltip('Editar'),
                ViewAction::make()
                    ->hiddenLabel()
                    ->tooltip('Visualizar'),
                DeleteAction::make()
                    ->hiddenLabel()
                    ->tooltip('Excluir'),
                Action::make('imprimir')
                    ->hiddenLabel()
                    ->tooltip('Imprimir')
                    ->label('Imprimir')
                    ->icon('heroicon-o-printer')
                    ->url(fn(ProcessoEvento $record): string => route('imprimir.solicitacao', $record->id))
                    ->visible(fn(ProcessoEvento $record) => ($record->tipoevento_id == config('settings.processoevento.reserva') || $record->tipoevento_id == config('settings.processoevento.empenho')))
                    ->openUrlInNewTab(),
            ])
            ->toolBarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->selectCurrentPageOnly()
            ->paginated([50, 100, 150, 200, 'all'])
            ->defaultPaginationPageOption(50)
            ->striped()
            ->deferLoading();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProcessoEventos::route('/'),
            'create' => Pages\CreateProcessoEvento::route('/create'),
            'edit' => Pages\EditProcessoEvento::route('/{record}/edit'),
        ];
    }
}
