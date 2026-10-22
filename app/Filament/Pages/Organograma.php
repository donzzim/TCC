<?php

namespace App\Filament\Pages;

use App\Models\Setores;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class Organograma extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-group';
    protected string $view = 'filament.pages.organograma';
    protected static ?string $title = 'Organograma';
    protected static ?string $navigationLabel = 'Organograma';
    protected static string | \UnitEnum | null $navigationGroup = 'Controle de Acesso';
    protected static ?int $navigationSort = 4;

    public function getViewData(): array
    {
        $setores = Setores::query()
            ->with(['setores.perfil'])
            ->orderBy('setor')
            ->get();

        $porPai = $setores->groupBy(fn (Setores $setor): string => (string) ($setor->codigopai ?? 'root'));

        $montarArvore = function (Setores $setor) use (&$montarArvore, $porPai): void {
            $filhos = $porPai->get((string) $setor->id, collect())->values();
            $setor->setRelation('filhos', $filhos);

            foreach ($filhos as $filho) {
                $montarArvore($filho);
            }
        };

        $raizId = Session::get('setorprincipal');
        $raiz = $raizId ? $setores->firstWhere('id', $raizId) : null;

        if ($raiz) {
            $montarArvore($raiz);
            $setoresRaiz = collect([$raiz]);
        } else {
            $setoresRaiz = $porPai->get('root', collect())->values();
            foreach ($setoresRaiz as $setor) {
                $montarArvore($setor);
            }
        }

        return [
            'setoresRaiz' => $setoresRaiz,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addSetorRaiz')
                ->label('Novo Setor Raiz')
                ->icon('heroicon-o-plus')
                ->form([
                    TextInput::make('setor')
                        ->label('Nome do Setor')
                        ->required(),
                    TextInput::make('cd_orgao')
                        ->label('Código do Órgão'),
                ])
                ->action(function (array $data): void {
                    Setores::create([
                        'setor' => $data['setor'],
                        'cd_orgao' => $data['cd_orgao'] ?? null,
                        'codigopai' => null,
                    ]);

                    Notification::make()
                        ->title('Setor raiz criado com sucesso!')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function addSetorFilhoAction(): Action
    {
        return Action::make('addSetorFilho')
            ->modalHeading('Adicionar Sub-setor')
            ->form([
                TextInput::make('setor')
                    ->label('Nome do Setor')
                    ->required(),
                TextInput::make('cd_orgao')
                    ->label('Código do Órgão'),
            ])
            ->action(function (array $data, array $arguments): void {
                $parentId = (int) ($arguments['parent_id'] ?? 0);

                if (! Setores::query()->whereKey($parentId)->exists()) {
                    Notification::make()
                        ->title('Setor pai não encontrado.')
                        ->danger()
                        ->send();

                    return;
                }

                Setores::create([
                    'setor' => $data['setor'],
                    'cd_orgao' => $data['cd_orgao'] ?? null,
                    'codigopai' => $parentId,
                ]);

                Notification::make()
                    ->title('Sub-setor criado com sucesso!')
                    ->success()
                    ->send();
            });
    }

    public function addUsuarioLotacaoAction(): Action
    {
        return Action::make('addUsuarioLotacao')
            ->modalHeading('Lotar Usuário no Setor')
            ->form([
                Select::make('user_id')
                    ->label('Selecione o Usuário')
                    ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->required(),
            ])
            ->action(function (array $data, array $arguments): void {
                $setorId = (int) ($arguments['setor_id'] ?? 0);
                $user = User::find($data['user_id'] ?? null);

                if (! $user || ! Setores::query()->whereKey($setorId)->exists()) {
                    Notification::make()
                        ->title('Usuário ou setor não encontrado.')
                        ->danger()
                        ->send();

                    return;
                }

                DB::transaction(function () use ($user, $setorId): void {
                    $user->lotacao()->update(['principal' => false]);
                    $user->setores()->syncWithoutDetaching([
                        $setorId => [
                            'principal' => true,
                            'atualizado_por' => auth()->id(),
                        ],
                    ]);
                });

                Notification::make()
                    ->title('Usuário lotado com sucesso!')
                    ->success()
                    ->send();
            });
    }

    public function editUsuarioAction(): Action
    {
        return Action::make('editUsuario')
            ->modalHeading(function (array $arguments): string {
                $user = User::find($arguments['user_id'] ?? null);

                return $user ? "Editar Lotação: {$user->name}" : 'Editar Lotação';
            })
            ->modalWidth('5xl')
            ->form(function (array $arguments): array {
                $user = User::with('setores')->find($arguments['user_id'] ?? null);
                $todosSetores = Setores::getHierarquiaOpcoes();
                $setoresAtuais = $user?->setores->pluck('id')->all() ?? [];
                $principalAtual = $user?->setorPrincipal()->value('setores.id');
                $sugestaoTexto = 'Sem registro de setor no recadastro.';

                if ($user?->recadastro?->nomesetor) {
                    $nomeSugerido = $user->recadastro->nomesetor;
                    $melhorSimilaridade = 0.0;
                    $nomeEncontrado = null;
                    $setorEncontrado = null;

                    foreach ($todosSetores as $id => $nomeBanco) {
                        similar_text(
                            mb_strtoupper(trim($nomeSugerido)),
                            mb_strtoupper(trim($nomeBanco)),
                            $percentual,
                        );

                        if ($percentual > $melhorSimilaridade) {
                            $melhorSimilaridade = $percentual;
                            $setorEncontrado = $id;
                            $nomeEncontrado = $nomeBanco;
                        }
                    }

                    if ($melhorSimilaridade >= 75) {
                        $sugestaoTexto = "{$nomeSugerido} (Sugestão: {$nomeEncontrado})";
                        $setoresAtuais = [$setorEncontrado];
                        $principalAtual = $setorEncontrado;
                    } else {
                        $sugestaoTexto = "{$nomeSugerido} (nenhuma correspondência próxima)";
                        $setorEncontrado = null;
                    }
                }

                return [
                    Placeholder::make('sugestao_recadastro')
                        ->label('Sugestão de setor via recadastro')
                        ->content($sugestaoTexto),
                    Select::make('setores')
                        ->label('Setores de lotação')
                        ->multiple()
                        ->options($todosSetores)
                        ->default($setoresAtuais)
                        ->searchable()
                        ->required()
                        ->live(),
                    Select::make('setor_principal')
                        ->label('Setor principal')
                        ->options(function (Get $get) use ($todosSetores): array {
                            $selecionados = $get('setores') ?? [];

                            return array_intersect_key($todosSetores, array_flip($selecionados));
                        })
                        ->default($principalAtual ?? ($setorEncontrado && in_array($setorEncontrado, $setoresAtuais, true) ? $setorEncontrado : null))
                        ->searchable()
                        ->required(),
                ];
            })
            ->action(function (array $data, array $arguments): void {
                $user = User::find($arguments['user_id'] ?? null);
                $setores = array_values(array_filter($data['setores'] ?? []));
                $principal = $data['setor_principal'] ?? null;

                if (! $user || ! in_array($principal, $setores)) {
                    Notification::make()
                        ->title('Selecione um setor principal entre os setores de lotação.')
                        ->danger()
                        ->send();

                    return;
                }

                $syncData = [];
                foreach ($setores as $setorId) {
                    $syncData[$setorId] = [
                        'principal' => (int) $setorId === (int) $principal,
                        'atualizado_por' => auth()->id(),
                    ];
                }

                $user->setores()->sync($syncData);

                Notification::make()
                    ->title('Lotação salva com sucesso!')
                    ->success()
                    ->send();
            })
            ->extraModalFooterActions(function (Action $action): array {
                $userId = $action->getArguments()['user_id'] ?? null;

                return [
                    Action::make('editar_perfil_completo')
                        ->label('Editar Perfil Completo')
                        ->color('gray')
                        ->icon('heroicon-o-pencil-square')
                        ->url($userId ? \App\Filament\Resources\UserResource::getUrl('edit', ['record' => $userId]) : '#')
                        ->openUrlInNewTab(),
                ];
            });
    }

    public function editSetorAction(): Action
    {
        return Action::make('editSetor')
            ->modalHeading('Editar Setor')
            ->modalWidth('md')
            ->form(function (array $arguments): array {
                $setorId = isset($arguments['setor_id']) ? (int) $arguments['setor_id'] : null;

                return [
                    TextInput::make('setor')
                        ->label('Nome do Setor')
                        ->required(),
                    Select::make('codigopai')
                        ->label('Setor Pai')
                        ->options(Setores::getHierarquiaOpcoes(null, $setorId))
                        ->searchable()
                        ->placeholder('Nenhum (Setor Raiz)'),
                    TextInput::make('unidadeorganizacional')
                        ->label('Unidade Organizacional'),
                    TextInput::make('codigodauo')
                        ->label('Código DAUO'),
                    TextInput::make('cd_orgao')
                        ->label('Código do Órgão'),
                ];
            })
            ->mountUsing(function (Schema $form, array $arguments): void {
                $setor = Setores::find($arguments['setor_id'] ?? null);

                if ($setor) {
                    $form->fill($setor->toArray());
                }
            })
            ->action(function (array $data, array $arguments): void {
                $setor = Setores::find($arguments['setor_id'] ?? null);

                if (! $setor) {
                    Notification::make()->title('Setor não encontrado.')->danger()->send();

                    return;
                }

                if ((int) ($data['codigopai'] ?? 0) === (int) $setor->id) {
                    Notification::make()
                        ->title('Um setor não pode ser pai dele mesmo.')
                        ->danger()
                        ->send();

                    return;
                }

                $setor->update([
                    ...$data,
                    'codigopai' => $data['codigopai'] ?: null,
                ]);

                Notification::make()
                    ->title('Setor atualizado com sucesso!')
                    ->success()
                    ->send();
            });
    }
}
