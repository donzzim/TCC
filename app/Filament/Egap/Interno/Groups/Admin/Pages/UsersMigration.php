<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Pages;

use App\Filament\Egap\Interno\Clusters\AdminEgapCluster;
use App\Filament\Egap\Interno\Permissions\Admin\AdminPage;
use App\Helper\CpfHelper;
use App\Models\egap\Admin\Lotacao as LotacaoEGAP;
use App\Models\egap\UserEgap;
use App\Models\egap\UserLotacao as LotacaoEMES;
use App\Models\egap\UserInfo as UserInfoEMES;
use App\Models\egap\Cadastro\Setores as SetoresEMES;
use App\Models\egap\User as UserEmes;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Spatie\Permission\Models\Role;

/**
 * Migra um usuário do EGAP legado (egap_jos_users) para o EMES (users), no mesmo
 * formato do migracao_01_usuarios.sql:
 *
 * - egap_jos_users  -> users        (username->login, block->!ativo, password como está)
 * - egap_mat_infousers -> users/user_perfil (cpf, matricula -> users; cargo -> user_perfil)
 * - egap_mat_lotacao   -> user_lotacao (setor->setor_id: ids de setores foram preservados)
 * - role `egap_user` (guard egap)
 *
 * O CPF é a chave entre as bases (ids de users e egap_jos_users colidem).
 */
class UsersMigration extends AdminPage implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedBolt;
    protected static string | \BackedEnum | null $activeNavigationIcon = 'heroicon-s-bolt';

    protected static ?string $title = 'Migração de Usuários';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'users-migration';

    protected static ?string $cluster = AdminEgapCluster::class;

    protected static ?string $navigationLabel = 'Migração';

    protected ?string $subheading = 'Cadastre ou migre usuários para o novo sistema EGAP';

    protected string $view = 'egap.filament.pages.admin-egap.users-migration';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Select::make('user')
                    ->label('Usuário do EGAP')
                    ->placeholder('Digite nome para pesquisar')
                    ->searchable()
                    ->live()
                    ->native(false)
                    ->prefixIcon(Heroicon::OutlinedUser)
                    ->options(UserEgap::query()->pluck('name', 'id')->toArray())
                    ->suffixAction(
                        Action::make('find_user')
                            ->label('Buscar')
                            ->tooltip('Preencher dados do usuário')
                            ->icon(Heroicon::OutlinedBarsArrowDown)
                            ->action(fn (Get $get, Set $set) => $this->hidratarUsuario($get('user'), $set)),
                    )
                    ->columnSpanFull(),

                Wizard::make([
                    Wizard\Step::make('Dados Pessoais')
                        ->icon(Heroicon::OutlinedUser)
                        ->description('Informações pessoais e de acesso do novo usuário')
                        ->schema([
                            Section::make('Informações principais')
                                ->description('Dados básicos de identificação do usuário.')
                                ->icon(Heroicon::OutlinedIdentification)
                                ->columns(2)
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Nome completo')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpanFull(),

                                    TextInput::make('login')
                                        ->label('Login')
                                        ->required()
                                        ->maxLength(255)
                                        ->unique(UserEmes::class, 'login', modifyRuleUsing: fn (Unique $rule, Get $get) => $this->ignorarMesmoCpf($rule, $get))
                                        ->validationMessages(['unique' => 'Este login já pertence a outro usuário do EMES.'])
                                        ->prefixIcon(Heroicon::OutlinedUserCircle)
                                        ->suffixAction(
                                            Action::make('gerar_login')
                                                ->label('Gerar novo login')
                                                ->tooltip('Monta o login no padrão do EMES a partir do nome e do CPF')
                                                ->icon(Heroicon::OutlinedSparkles)
                                                ->action(fn (Get $get, Set $set) => $this->gerarLogin($get, $set)),
                                        ),

                                    TextInput::make('email')
                                        ->label('E-mail')
                                        ->email()
                                        ->required()
                                        ->maxLength(255)
                                        ->unique(UserEmes::class, 'email', modifyRuleUsing: fn (Unique $rule, Get $get) => $this->ignorarMesmoCpf($rule, $get))
                                        ->validationMessages(['unique' => 'Este e-mail já pertence a outro usuário do EMES.'])
                                        ->prefixIcon(Heroicon::OutlinedEnvelope),
                                ])
                                ->columnSpanFull(),

                            Section::make('Dados pessoais')
                                ->description('Informações utilizadas para identificação do servidor.')
                                ->icon(Heroicon::OutlinedIdentification)
                                ->columns(2)
                                ->schema([
                                    TextInput::make('cpf')
                                        ->label('CPF')
                                        ->mask('999.999.999-99')
                                        ->required()
                                        ->placeholder('000.000.000-00')
                                        ->maxLength(14)
                                        ->prefixIcon(Heroicon::OutlinedIdentification),

                                    TextInput::make('telefone')
                                        ->label('Telefone')
                                        ->mask('(99) 99999-9999')
                                        ->tel()
                                        ->helperText('Opcional')
                                        ->prefixIcon(Heroicon::OutlinedPhone),

                                    TextInput::make('matricula')
                                        ->label('Matrícula')
                                        ->helperText('Opcional')
                                        ->prefixIcon(Heroicon::OutlinedDocumentText),

                                    TextInput::make('numero_funcional')
                                        ->label('Número funcional')
                                        ->helperText('Opcional')
                                        ->prefixIcon(Heroicon::OutlinedHashtag),

                                    TextInput::make('cargo')
                                        ->label('Cargo')
                                        ->helperText('Opcional')
                                        ->columnSpanFull()
                                        ->prefixIcon(Heroicon::OutlinedBriefcase),
                                ])
                                ->columnSpanFull(),
                        ]),

                    Wizard\Step::make('Lotação')
                        ->icon(Heroicon::OutlinedBuildingOffice)
                        ->description('Defina as unidades e setores vinculados ao usuário')
                        ->schema([
                            Repeater::make('lotacoes')
                                ->label('Lotações do usuário')
                                ->defaultItems(1)
                                ->addActionLabel('Adicionar lotação')
                                ->reorderable()
                                ->itemLabel(
                                    fn (array $state): ?string =>
                                        $state['setor_id'] ?? null
                                            ? 'Lotação configurada'
                                            : 'Nova lotação'
                                )
                                ->columns(3)
                                ->schema([
                                    Select::make('UnidadeJudiciaria')
                                        ->label('Unidade Judiciária')
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->live()
                                        ->native(false)
                                        ->placeholder('Selecione a unidade')
                                        ->prefixIcon(Heroicon::OutlinedBuildingOffice2)
                                        ->options(
                                            fn () => SetoresEMES::query()
                                                //->whereColumn('id', 'codigopai')
                                                ->orderBy('unidadeorganizacional')
                                                ->pluck('unidadeorganizacional', 'codigopai')
                                                ->toArray()
                                        )
                                        ->afterStateUpdated(
                                            fn (Set $set) => $set('setor_id', null)
                                        ),

                                    Select::make('setor_id')
                                        ->label('Setor')
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->native(false)
                                        ->placeholder('Selecione o setor')
                                        ->prefixIcon(Heroicon::OutlinedMapPin)
                                        ->options(
                                            fn (Get $get) => blank($get('UnidadeJudiciaria'))
                                                ? []
                                                : SetoresEMES::query()
                                                    ->where('codigopai', $get('UnidadeJudiciaria'))
                                                    //->whereColumn('id', '<>', 'codigopai')
                                                    ->orderBy('setor')
                                                    ->pluck('setor', 'id')
                                                    ->toArray()
                                        )
                                        ->disabled(
                                            fn (Get $get) =>
                                                blank($get('UnidadeJudiciaria'))
                                        )
                                        ->helperText(
                                            'Selecione primeiro a unidade judiciária.'
                                        ),

                                    Toggle::make('principal')
                                        ->label('Setor principal')
                                        ->onColor('success')
                                        ->offColor('gray')
                                        ->inline(false)
                                        ->live()
                                        ->helperText(
                                            'Apenas uma lotação pode ser marcada como principal.'
                                        )
                                        ->default(false)
                                        ->afterStateUpdated(function (?bool $state, Toggle $component) {
                                            if (! $state) {
                                                return;
                                            }

                                            // data.lotacoes.{chave-do-item}
                                            $itemPath = $component->getContainer()->getStatePath();
                                            $repeaterPath = Str::beforeLast($itemPath, '.');
                                            $itemKey = Str::afterLast($itemPath, '.');

                                            // Desliga só o `principal` dos outros itens, direto no estado do
                                            // Livewire. Regravar o array inteiro do repeater (como antes)
                                            // re-hidrata todos os itens e faz os toggles brigarem entre si;
                                            // além disso, as chaves podem ser int (itens vindos do
                                            // hidratarUsuario) e a comparação estrita com a string da
                                            // statePath falhava, desmarcando inclusive o item clicado.
                                            foreach (array_keys(data_get($this, $repeaterPath) ?? []) as $key) {
                                                if ((string) $key !== $itemKey) {
                                                    data_set($this, "{$repeaterPath}.{$key}.principal", false);
                                                }
                                            }
                                        }),
                                ])
                                ->columnSpanFull(),
                        ]),

                    Wizard\Step::make('Execução da migração')
                        ->icon(Heroicon::OutlinedArrowPathRoundedSquare)
                        ->description('Confirme os dados e execute a migração')
                        ->schema([
                            Section::make('Resumo do usuário')
                                ->description('Confira os dados antes de enviar para o banco.')
                                ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                                ->columns(2)
                                ->schema([
                                    Placeholder::make('resumo_nome')
                                        ->label('Nome completo')
                                        ->content(fn (Get $get) => $get('name') ?: '—'),

                                    Placeholder::make('resumo_login')
                                        ->label('Login')
                                        ->content(fn (Get $get) => $get('login') ?: '—'),

                                    Placeholder::make('resumo_email')
                                        ->label('E-mail')
                                        ->content(fn (Get $get) => $get('email') ?: '—'),

                                    Placeholder::make('resumo_cpf')
                                        ->label('CPF')
                                        ->content(fn (Get $get) => $get('cpf') ?: '—'),

                                    Placeholder::make('resumo_matricula')
                                        ->label('Matrícula')
                                        ->content(fn (Get $get) => $get('matricula') ?: '—'),

                                    Placeholder::make('resumo_cargo')
                                        ->label('Cargo')
                                        ->content(fn (Get $get) => $get('cargo') ?: '—'),

                                    Placeholder::make('resumo_status')
                                        ->label('Status')
                                        ->content(fn (Get $get) => ($get('ativo') ?? true) ? 'Ativo' : 'Inativo'),

                                    Placeholder::make('resumo_cadastro')
                                        ->label('Situação no EMES')
                                        ->content(
                                            fn (Get $get) => filled($get('emes_user_id'))
                                                ? "Usuário já cadastrado (ID {$get('emes_user_id')}) — os dados acima irão atualizar o registro existente."
                                                : 'Novo cadastro — nenhum usuário com este CPF foi encontrado no EMES.'
                                        )
                                        ->columnSpanFull(),

                                    Placeholder::make('resumo_lotacoes')
                                        ->label('Lotações')
                                        ->content(function (Get $get) {
                                            $lotacoes = collect($get('lotacoes') ?? []);

                                            if ($lotacoes->isEmpty()) {
                                                return 'Nenhuma lotação informada.';
                                            }

                                            $setores = SetoresEMES::query()
                                                ->whereIn('id', $lotacoes->pluck('setor_id'))
                                                ->pluck('setor', 'id');

                                            return new HtmlString(
                                                $lotacoes
                                                    ->map(function (array $item) use ($setores): string {
                                                        $nome = e($setores->get($item['setor_id'] ?? null)
                                                            ?? 'Setor #' . ($item['setor_id'] ?? '?'));

                                                        return ($item['principal'] ?? false)
                                                            ? "<strong>{$nome} (principal)</strong>"
                                                            : $nome;
                                                    })
                                                    ->implode('<br>')
                                            );
                                        })
                                        ->columnSpanFull(),
                                ]),

                            SchemaActions::make([
                                Action::make('migrar')
                                    ->label('Cadastrar')
                                    ->icon(Heroicon::OutlinedPencilSquare)
                                    ->color('primary')
                                    ->requiresConfirmation()
                                    ->modalDescription('Os dados revisados acima serão gravados em nossos registros. Deseja continuar?')
                                    ->action(fn () => $this->submit()),
                            ])->alignEnd(),
                        ]),
                ])
                    ->columnSpanFull(),
            ]);
    }

    public function hidratarUsuario(?string $userId, Set $set): void
    {
        if (blank($userId)) {
            return;
        }

        $egap = UserEgap::query()
            ->with(['infoUser', 'lotacoes.setorRef'])
            ->find($userId);

        if (! $egap) {
            Notification::make()
                ->title('Usuário não encontrado')
                ->body('O registro selecionado não existe mais na base do EGAP.')
                ->danger()
                ->send();

            return;
        }

        $cpfDigits = preg_replace('/\D/', '', (string) $egap->infoUser?->cpf) ?: null;

        $usuarioExistente = filled($cpfDigits)
            ? UserEmes::query()->where('cpf', $cpfDigits)->first()
            : null;

        if ($usuarioExistente) {
            Notification::make()
                ->title('Usuário já cadastrado no EMES')
                ->body("Já existe um cadastro para este CPF (ID {$usuarioExistente->id}, login \"{$usuarioExistente->login}\"). Os dados abaixo irão atualizar o registro existente em vez de criar um novo.")
                ->warning()
                ->persistent()
                ->send();
        }

        $set('name', $egap->name);
        $set('login', $egap->username);
        $set('email', $egap->email);
        $set('cpf', CpfHelper::format($egap->infoUser?->cpf));
        $set('telefone', null);
        $matriculaDigits = preg_replace('/\D/', '', (string) $egap->infoUser?->matricula);
        $set('matricula', filled($matriculaDigits) && (int) $matriculaDigits !== 0 ? $matriculaDigits : null);
        $set('numero_funcional', null);
        $set('cargo', $egap->infoUser?->cargo);
        $set('ativo', ! $egap->block);
        $set('password', $egap->password);
        $set('emes_user_id', $usuarioExistente?->id);

        $setoresNaoEncontrados = [];

        $lotacoes = $egap->lotacoes
            ->map(function (LotacaoEGAP $lotacao) use (&$setoresNaoEncontrados): ?array {
                $setor = $lotacao->setorRef;

                if (! $setor) {
                    $setoresNaoEncontrados[] = "setor #{$lotacao->setor}";

                    return null;
                }

                return [
                    'UnidadeJudiciaria' => $setor->codigopai,
                    'setor_id' => $setor->getKey(),
                    'principal' => false,
                ];
            })
            ->filter()
            ->unique('setor_id')
            ->values()
            ->all();

        if ($lotacoes !== []) {
            $lotacoes[0]['principal'] = true;
        }

        $set('lotacoes', $lotacoes);

        if ($setoresNaoEncontrados !== []) {
            Notification::make()
                ->title('Algumas lotações do EGAP apontam para setores inexistentes')
                ->body('Vincule manualmente: ' . implode(', ', array_unique($setoresNaoEncontrados)) . '.')
                ->warning()
                ->send();
        }

        $this->notificarConsistencia($egap, $lotacoes);
    }

    public function gerarLogin(Get $get, Set $set): void
    {
        $nome = trim((string) $get('name'));

        $cpfDigitos = preg_replace('/\D/', '', (string) $get('cpf'));

        if (strlen((string) $cpfDigitos) !== 11) {
            Notification::make()
                ->title('CPF não informado')
                ->body('Preencha um CPF válido (11 dígitos) antes de gerar o login.')
                ->danger()
                ->send();

            return;
        }

        if (blank($nome)) {
            Notification::make()
                ->title('Nome não informado')
                ->body('Preencha o nome completo antes de gerar o login.')
                ->danger()
                ->send();

            return;
        }

        $cpfFormatado = CpfHelper::format((string) $get('cpf'));

        $login = $this->montarLoginEmes($nome, $cpfFormatado);

        $set('cpf', $cpfFormatado);
        $set('login', $login);

        Notification::make()
            ->title('Login gerado')
            ->body("Novo login: {$login}")
            ->success()
            ->send();
    }

    private function montarLoginEmes(string $nome, string $cpfFormatado): string
    {
        $partes = explode(' ', strtolower($nome));

        $iniciais = collect($partes)
            ->map(fn (string $parte): string => Str::substr($parte, 0, 1))
            ->implode('');

        $ultimoSobrenome = end($partes) ?: '';
        $prefixoCpf = Str::substr($cpfFormatado, 0, 3);

        return $iniciais . $ultimoSobrenome . $prefixoCpf;
    }

    private function notificarConsistencia(UserEgap $egap, array $lotacoes): void
    {
        $pendencias = [];

        if (blank($egap->name)) {
            $pendencias[] = 'Nome completo';
        }

        if (blank($egap->username)) {
            $pendencias[] = 'Login';
        }

        if (blank($egap->email)) {
            $pendencias[] = 'E-mail';
        } elseif (! filter_var($egap->email, FILTER_VALIDATE_EMAIL)) {
            $pendencias[] = 'E-mail (formato inválido)';
        }

        if (strlen((string) preg_replace('/\D/', '', (string) $egap->infoUser?->cpf)) !== 11) {
            $pendencias[] = 'CPF';
        }

        if ($lotacoes === []) {
            $pendencias[] = 'Lotação (nenhum setor vinculado no EGAP)';
        } else {
            foreach ($lotacoes as $indice => $lotacao) {
                if (blank($lotacao['UnidadeJudiciaria']) || blank($lotacao['setor_id'])) {
                    $pendencias[] = 'Lotação #' . ($indice + 1) . ' (unidade/setor)';
                }
            }
        }

        if ($pendencias === []) {
            Notification::make()
                ->title('Dados de ' . $egap->name . ' carregados')
                ->body('Todos os campos obrigatórios foram preenchidos a partir do EGAP.')
                ->success()
                ->send();

            return;
        }

        Notification::make()
            ->title('Dados carregados com pendências')
            ->body('Ajuste manualmente antes de migrar: ' . implode(', ', $pendencias) . '.')
            ->warning()
            ->persistent()
            ->send();
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        $cpf = preg_replace('/\D/', '', (string) ($data['cpf'] ?? ''));

        if (strlen($cpf) !== 11) {
            Notification::make()
                ->title('CPF inválido')
                ->body('Informe um CPF válido (11 dígitos) antes de enviar a migração.')
                ->danger()
                ->send();

            return;
        }

        $ativo = (bool) ($this->data['ativo'] ?? true);
        $password = $this->data['password'] ?? null;

        $lotacoes = collect($data['lotacoes'] ?? [])
            ->filter(fn (array $lotacao): bool => filled($lotacao['setor_id'] ?? null))
            ->unique('setor_id');

        $role = Role::findOrCreate(UserEmes::ROLE_USER, UserEmes::GUARD);

        $usuario = DB::connection('emes')->transaction(function () use ($data, $cpf, $ativo, $password, $lotacoes, $role): UserEmes {
            $usuario = UserEmes::on('emes')->updateOrCreate(['cpf' => $cpf], [
                'name' => $data['name'],
                'login' => $data['login'],
                'email' => $data['email'],
                'telefone' => preg_replace('/\D/', '', (string) ($data['telefone'] ?? '')) ?: null,
                'matricula' => $data['matricula'] ?? null,
                'numero_funcional' => $data['numero_funcional'] ?? null,
                'ativo' => $ativo,
            ]);

            if (filled($password)) {
                $usuario->newQuery()->whereKey($usuario->getKey())->update(['password' => $password]);
            }

            if (filled($data['cargo'] ?? null)) {
                UserInfoEMES::query()->updateOrCreate(
                    ['user_id' => $usuario->id],
                    ['cargo' => $data['cargo']],
                );
            }

            foreach ($lotacoes as $lotacao) {
                LotacaoEMES::query()->updateOrCreate(
                    ['user_id' => $usuario->id, 'setor_id' => $lotacao['setor_id']],
                    ['principal' => (bool) ($lotacao['principal'] ?? false)],
                );
            }

            $principal = $lotacoes->firstWhere('principal', true);

            if ($principal) {
                LotacaoEMES::query()
                    ->where('user_id', $usuario->id)
                    ->where('setor_id', '<>', $principal['setor_id'])
                    ->where('principal', true)
                    ->update(['principal' => false]);
            }

            $usuario->assignRole($role);

            return $usuario;
        });

        Notification::make()
            ->title('Usuário migrado com sucesso')
            ->body($usuario->name . ' foi ' . ($usuario->wasRecentlyCreated ? 'cadastrado' : 'atualizado') . ' no EMES com a role ' . UserEmes::ROLE_USER . '.')
            ->success()
            ->send();

        $this->data = [];
        $this->form->fill();
        $this->redirect(static::getUrl(), navigate: true);
    }

    private function ignorarMesmoCpf(Unique $rule, Get $get): Unique
    {
        $cpf = preg_replace('/\D/', '', (string) $get('cpf'));

        return filled($cpf) ? $rule->whereNot('cpf', $cpf) : $rule;
    }
}
