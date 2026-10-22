<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Pages;

use App\Filament\Egap\Interno\Clusters\PatrimonioCluster;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\ValidarTermoResource;
use App\Filament\Egap\Interno\Permissions\Patrimonio\PatrimonioPage;
use App\Models\egap\Cadastro\ComplementoSetor;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\Cadastro\SituacaoBem;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use App\Services\Egap\Patrimonio\IncorporarBensService;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class IncorporarBensPage extends PatrimonioPage implements HasForms
{
    use InteractsWithForms;

    protected static ?string $cluster = PatrimonioCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-plus-circle';

    protected static string | \UnitEnum | null $navigationGroup = 'Bens Móveis';

    protected static ?string $navigationLabel = 'Incorporar bens';

    protected static ?string $title = 'Incorporar bens';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'bens-moveis/incorporar';

    protected string $view = 'egap.filament.pages.patrimonio.bens-moveis.incorporar-bens-page';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'faixas' => [],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Bem de referência')
                    ->description('Os dados deste bem serão usados como base para os novos patrimônios.')
                    ->icon('heroicon-o-document-duplicate')
                    ->schema([
                        Select::make('bem_referencia_id')
                            ->label('Bem de referência')
                            ->placeholder('Busque por patrimônio, descrição ou nota fiscal')
                            ->searchable()
                            ->required()
                            ->native(false)
                            ->optionsLimit(50)
                            ->getSearchResultsUsing(fn(string $search): array => $this->buscarBens($search))
                            ->getOptionLabelUsing(fn($value): ?string => $this->buscarRotuloDoBem($value))
                            ->createOptionForm($this->formularioNovoBemReferencia())
                            ->createOptionUsing(function (array $data): int {
                                return (int) BemMovel::query()->create($data)->getKey();
                            }),
                    ]),

                Section::make('Numeração dos novos bens')
                    ->description('Cada faixa gera uma cópia do bem de referência. Sem faixas, somente o bem de referência será incorporado.')
                    ->icon('heroicon-o-numbered-list')
                    ->schema([
                        Repeater::make('faixas')
                            ->label('Faixas de patrimônio')
                            ->defaultItems(0)
                            ->addActionLabel('Adicionar faixa')
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(function (array $state): ?string {
                                $inicio = $state['inicio'] ?? null;
                                $fim = $state['fim'] ?? null;

                                if (! $inicio) {
                                    return 'Nova faixa';
                                }

                                return $fim && $fim !== $inicio
                                    ? "Patrimônios {$inicio} a {$fim}"
                                    : "Patrimônio {$inicio}";
                            })
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('inicio')
                                        ->label('Núm. patrimônio inicial')
                                        ->numeric()
                                        ->integer()
                                        ->minValue(1)
                                        ->required()
                                        ->live(onBlur: true),

                                    TextInput::make('fim')
                                        ->label('Núm. patrimônio final')
                                        ->helperText('Deixe vazio para gerar apenas o número inicial.')
                                        ->numeric()
                                        ->integer()
                                        ->minValue(1)
                                        ->live(onBlur: true),

                                    Placeholder::make('quantidade')
                                        ->label('Quantidade')
                                        ->content(function (Get $get): string {
                                            $inicio = (int) $get('inicio');
                                            $fim = (int) ($get('fim') ?: $inicio);

                                            if ($inicio < 1 || $fim < $inicio) {
                                                return 'Faixa inválida';
                                            }

                                            return (string) ($fim - $inicio + 1);
                                        }),
                                ]),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function incorporar(IncorporarBensService $service): void
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
            $resultado = $service->incorporar(
                bemReferenciaId: (int) $data['bem_referencia_id'],
                faixas: $data['faixas'] ?? [],
                userId: (int) $userId,
            );

            Notification::make()
                ->title('Bens incorporados com sucesso.')
                ->body("Termo {$resultado->numeroTermo}/{$resultado->anoTermo} criado com {$resultado->quantidadeBens} bem(ns). Use a ação Upload do Termo para anexar o PDF.")
                ->success()
                ->send();

            $this->redirect(ValidarTermoResource::getUrl('index', [
                'tableSearch' => (string) $resultado->numeroTermo,
            ]));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Não foi possível incorporar os bens.')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    private function buscarBens(string $search): array
    {
        return BemMovel::query()
            ->where(function (Builder $query) use ($search): void {
                $query
                    ->where('Descricao', 'like', "%{$search}%")
                    ->orWhere('NotaFiscal', 'like', "%{$search}%");

                if (ctype_digit($search)) {
                    $query->orWhere('NumPatrimonio', (int) $search);
                }
            })
            ->latest('id')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn(BemMovel $bem): array => [$bem->getKey() => $this->formatarRotuloDoBem($bem)])
            ->all();
    }

    private function buscarRotuloDoBem($id): ?string
    {
        $bem = BemMovel::query()->find($id);

        return $bem ? $this->formatarRotuloDoBem($bem) : null;
    }

    private function formatarRotuloDoBem(BemMovel $bem): string
    {
        $patrimonio = $bem->NumPatrimonio ?: 'S/P';
        $descricao = Str::limit($bem->Descricao ?: 'Sem descrição', 80);
        $notaFiscal = $bem->NotaFiscal ?: 'S/N';

        return "Pat. {$patrimonio} - {$descricao} - NF {$notaFiscal}";
    }

    private function formularioNovoBemReferencia(): array
    {
        return [
            Grid::make(2)->schema([
                TextInput::make('NumPatrimonio')
                    ->label('Patrimônio')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->required(),

                Select::make('SituacaoBem')
                    ->label('Situação')
                    ->options(fn(): array => SituacaoBem::query()
                        ->orderBy('descricao')
                        ->pluck('descricao', 'id')
                        ->toArray())
                    ->searchable()
                    ->required()
                    ->native(false),

                Textarea::make('Descricao')
                    ->label('Descrição detalhada')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),

                Select::make('UnidadeJudiciaria')
                    ->label('Unidade judiciária')
                    ->options(fn(): array => Setores::query()
                        ->whereColumn('id', 'codigopai')
                        ->orderBy('setor')
                        ->pluck('setor', 'id')
                        ->toArray())
                    ->searchable()
                    ->native(false),

                Select::make('Setor')
                    ->label('Setor')
                    ->options(fn(): array => Setores::query()
                        ->whereColumn('id', '<>', 'codigopai')
                        ->orderBy('setor')
                        ->pluck('setor', 'id')
                        ->toArray())
                    ->searchable()
                    ->native(false),

                Select::make('ComplementoSetor')
                    ->label('Complemento do setor')
                    ->options(fn(): array => ComplementoSetor::query()
                        ->orderBy('descricao')
                        ->pluck('descricao', 'id')
                        ->toArray())
                    ->searchable()
                    ->native(false),

                TextInput::make('AndarSetor')
                    ->label('Andar'),

                TextInput::make('ValorAquisicao')
                    ->label('Valor de aquisição')
                    ->numeric()
                    ->prefix('R$'),
            ]),
        ];
    }
}
