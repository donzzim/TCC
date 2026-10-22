<?php

namespace App\Livewire;



use Exception;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Notifications\Notification;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Livewire\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TextInput\Mask;
use App\Models\Escola\CursoAlunos;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\CursoFrequencias;
use App\Models\Escola\Cursos;
use App\Models\Escola\TipoDeficiencia;
use App\Models\Escola\TipoGenero;
use App\Models\Escola\TipoPublico;
use App\Models\Escola\TipoRaca;
use App\Models\Escola\TipoSexo;
use App\Models\Perfil;
use App\Models\Recadastro;
use App\Models\Setores;
use App\Models\User;
use App\Services\EmailService;
use App\Services\Pedagogico\FrequenciaService;
use App\Services\Pedagogico\InscricaoService;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Validate;


class Inscricao extends Component implements HasForms, HasActions

{
    use InteractsWithActions;
    use InteractsWithForms;

    public array $data = [];
    public $uuid;
    public $tipouser;
    public $tiponome;
    public $usuario;
    public $concordo;
    public $cpf;
    public $sexo;
    public $setor_orgao;
    public $genero;
    public $externo;
    public $raca;
    public $nome;
    public $setor;
    public $password;
    public $atividades;
    public $passwordconfirm;
    public $novouser = false;
    public $nome_social;
    public $pcd = [];
    public bool $deficiente;
    public $email;
    public $telefone;
    public $validado = false;
    public bool $disponivel;
    public $curso;


    public function mount($uuid): void
    {
        $this->form->fill();
        $this->uuid = $uuid;
        // dd($uuid);
        $datahoje = Carbon::now();
        $row = Cursos::where('uuid', $uuid)
            ->where('ativo', true)
            ->where(function ($q) use ($datahoje) {
                $q->where(function ($q2) use ($datahoje) {
                    $q2->where('inicio_inscricao', '<=', $datahoje)
                        ->where('termino_inscricao', '>=', $datahoje);
                })
                    ->orWhere('liberar_inscricao', true);
            })
            ->first();
        if (!$row) {
            $row = CursoAtividades::where('uuid', $uuid)
                ->where('ativo', true)
                ->where(function ($q) use ($datahoje) {
                    $q->where(function ($q2) use ($datahoje) {
                        $q2->where('inicio_inscricao', '>=', $datahoje)
                            ->where('termino_inscricao', '<=', $datahoje);
                    })
                        ->orWhere('liberar_inscricao', true);
                })
                ->first();
            if (!$row) {
                $this->disponivel = false;
                return;
            }
        }
        $this->curso = $row;
        $this->disponivel = true;
    }



    public function form(Schema $form): Schema
    {

        return $form
            ->schema(

                [
                    Toggle::make('novouser')
                        ->hidden(true)
                        ->default(false)
                        ->live(),

                    Toggle::make('validado')
                        ->hidden(true)
                        ->default(false)
                        ->live(),


                    TextInput::make('cpf')
                        ->label('CPF')
                        ->live(onBlur: true)
                        ->required()
                        ->mask('999.999.999-99')
                        ->suffixAction(
                            Action::make('pesquisar')
                                ->icon('heroicon-o-magnifying-glass')
                                ->action(function (Get $get, Set $set) {
                                    $this->pesquisarUsuario($get('cpf'), $set);
                                })
                        )
                        ->extraAttributes([
                            'wire:keydown.enter.prevent' => "mountFormComponentAction('cpf', 'pesquisar')",
                        ])
                        ->maxLength(14)
                        ->columnSpanFull(),



                    Section::make()
                        ->label('Atividades')
                        ->schema([
                            select::make('atividades')
                                ->label('Atividades que deseja se inscrever(caso nenhuma seja selecionada o usuario será inscrito em todas)')
                                ->columnSpanFull()
                                ->multiple()
                                ->options(function () {
                                    return $this->curso->atividades->mapWithKeys(function ($atividade) {
                                        $inicio = Carbon::parse($atividade->inicio)->format('d/m/y');
                                        $termino = Carbon::parse($atividade->termino)->format('d/m/y');
                                        return [
                                            $atividade->id => "{$atividade->titulo} - {$inicio} até {$termino}",
                                        ];
                                    })->toArray();
                                }),
                        ])
                        ->visible(function () {
                            if (CursoAtividades::where('curso_id', $this->curso->id)->exists() && $this->validado) {
                                return true;
                            }
                            return false;
                        })
                    // ->collapsible()
                    ,


                    Section::make('cadastro')
                        ->schema([
                            TextInput::make('nome')
                                ->label('Nome Completo')
                                ->required()
                                ->rule(function (Get $get) {
                                    return function ($attribute, $value, $fail) use ($get) {
                                        $existente = User::where('name', $value)->whereNot('id', $usuario->id ?? -1)->exists();
                                        if ($this->usuario) {
                                            if ($this->usuario['name']) {
                                                return;
                                            }
                                        }

                                        if ($existente) {
                                            $fail('Esse nome ja está em uso, verifique se o cpf informado está correto');
                                            return;
                                        }
                                    };
                                })
                                ->disabled(function () {
                                    // dd($this->usuario);
                                    if ($this->usuario) {
                                        return $this->usuario['name'] ? true : false;
                                    }
                                    return false;
                                })
                                ->live()
                                ->maxLength(255)
                                // ->disabled(true)
                                ->columnSpanFull(),



                            Section::make('E-mail')
                                ->schema([
                                    TextInput::make('email')
                                        ->label('')
                                        ->hint('Atenção: Servidores, Estagiários e Magistrados, por favor, utilizem seu e-mail institucional do TJES para realizar a inscrição. Isso assegura a validação do seu vínculo e o recebimento de notificações oficiais.')
                                        // ->hintIcon('exclamation-triangle')
                                        ->hintColor('warning')
                                        ->rule(function (Get $get) {
                                            return function ($attribute, $value, $fail) use ($get) {
                                                $existente = User::where('email', $value)->whereNot('id', $usuario->id ?? -1)->exists();
                                                if ($this->usuario) {
                                                    if ($this->usuario['email']) {
                                                        return;
                                                    }
                                                }

                                                if ($existente) {
                                                    $fail('Email já existente, verifique se o cpf informado está correto');
                                                    return;
                                                }
                                                if (!EmailService::validarEmail($value)) {
                                                    $fail('Email inválido');
                                                    return;
                                                }
                                            };
                                        })
                                        ->live()
                                        ->disabled(function () {
                                            // dd($this->usuario);
                                            if ($this->usuario) {
                                                $email = $this->usuario['email'] ?? null;
                                                if ($email == null || $email == '') {
                                                    return false;
                                                }
                                                return true;
                                            }
                                            return false;
                                        })
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpanFull(),
                                ]),

                            TextInput::make('password')
                                ->label('Senha')
                                ->password()
                                ->revealable()
                                ->live()
                                ->rule(function () {
                                    return function ($attribute, $value, $fail) {

                                        if (! $value) {
                                            return;
                                        }

                                        // 1) Mínimo 8 caracteres
                                        if (strlen($value) < 8) {
                                            $fail('A senha deve ter pelo menos 8 caracteres.');
                                            return;
                                        }

                                        // 2) Pelo menos 1 letra
                                        if (! preg_match('/[A-Za-z]/', $value)) {
                                            $fail('A senha deve conter pelo menos uma letra.');
                                            return;
                                        }

                                        // 3) Pelo menos 1 número
                                        if (! preg_match('/\d/', $value)) {
                                            $fail('A senha deve conter pelo menos um número.');
                                            return;
                                        }

                                        // 4) Pelo menos 1 caractere especial
                                        if (! preg_match('/[^A-Za-z0-9]/', $value)) {
                                            $fail('A senha deve conter pelo menos um caractere especial.');
                                            return;
                                        }
                                    };
                                })
                                ->required()
                                ->hidden(fn() => $this->usuario && $this->usuario['password'])
                                ->maxLength(255)
                                ->columnSpanFull(),


                            TextInput::make('passwordconfirm')
                                ->label('Confirmar Senha')
                                ->password()
                                ->revealable()
                                ->live()
                                ->rule(function (Get $get, Set $set) {
                                    return function ($attribute, $value, $fail) use ($get, $set) {
                                        if ($get('password') != $get('passwordconfirm'))
                                            $fail('As senhas devem ser iguais');

                                        return;
                                    };
                                })
                                ->required()
                                ->hidden(function () {
                                    if ($this->usuario) {
                                        return $this->usuario['password'] ? true : false;
                                    }
                                    return false;
                                })
                                ->maxLength(255)
                                ->columnSpanFull(),



                            TextInput::make('telefone')
                                ->label('Telefone')
                                ->disabled(function () {
                                    // dd($this->usuario);
                                    if ($this->usuario) {
                                        return $this->usuario['telefone'] ? true : false;
                                    }
                                    return false;
                                })
                                ->required()
                                ->live()

                                // ->maxLength(15)
                                ->columnSpanFull(),

                            TextInput::make('nome_social')
                                ->label('Nome Social')
                                // ->disabled(function () {
                                //     // dd($this->usuario);
                                //     if ($this->usuario) {
                                //         return $this->usuario['nome_social'] ? true : false;
                                //     }
                                //     return false;
                                // })
                                ->maxLength(255)
                                ->live()

                                ->columnSpanFull(),


                            Select::make('sexo')
                                ->label('Sexo')
                                // ->disabled(function () {
                                //     // dd($this->usuario);
                                //     if ($this->usuario)
                                //         return $this->usuario['sexo'] ? true : false;

                                //     return false;
                                // })
                                ->required()
                                ->options(function () {
                                    return TipoSexo::all()->pluck('descricao', 'id');
                                })
                                ->columnSpanFull(),

                            Select::make('genero')
                                ->label('Gênero')
                                // ->disabled(function () {
                                //     // dd($this->usuario);
                                //     if ($this->usuario) {
                                //         if ($this->usuario->perfil)
                                //             return $this->usuario['genero'] ? true : false;
                                //     }
                                //     return false;
                                // })
                                ->required()
                                ->options(function () {
                                    return TipoGenero::all()->pluck('descricao', 'id');
                                })
                                ->columnSpanFull(),

                            Select::make('raca')
                                ->label('Cor/Raça')
                                // ->disabled(function () {
                                //     // dd($this->usuario);
                                //     if ($this->usuario) {
                                //         return $this->usuario['raca'] ? true : false;
                                //     }
                                //     return false;
                                // })
                                ->required()
                                ->options(function () {
                                    return TipoRaca::all()->pluck('descricao', 'id');
                                })
                                ->columnSpanFull(),

                            Toggle::make('externo')
                                ->label('Publico Externo')
                                ->default(false)
                                ->live(),

                            Select::make('tipouser')
                                ->label('Vinculo')
                                // ->disabled(function () {
                                //     // dd($this->usuario);
                                //     if ($this->usuario) {
                                //         return $this->usuario->setorPrincipal()->first() ? true : false;
                                //     }
                                //     return false;
                                // })
                                // ->searchable()
                                ->options(function () {
                                    return TipoPublico::where('id', '>', 2101)->where('id', '<', 2205)->whereNotIn('id', [2106])->get()->pluck('descricao', 'id');
                                })
                                ->required()
                                ->columnSpanFull()
                                ->hidden(fn() => $this->externo)
                                ->disabled(fn() => $this->externo),



                            Select::make('setor')
                                ->label('Setor')
                                ->live()
                                // ->disabled(function () {
                                //     // dd($this->usuario);
                                //     if ($this->usuario) {
                                //         return $this->usuario['setor'] ? true : false;
                                //     }
                                //     return false;
                                // })
                                ->searchable()
                                ->options(function () {
                                    return Setores::all()->pluck('setor', 'id');
                                })
                                ->required()
                                ->hidden(fn() => $this->externo)
                                ->disabled(fn() => $this->externo)
                                ->columnSpanFull(),

                            RichEditor::make('setor_orgao')
                                ->label('Vínculo empregaticío')
                                ->placeholder('Informe o nome da empresa, órgão público ou instituição onde você trabalha atualmente. Se você for autônomo, profissional liberal ou freelancer, escreva "Autônomo" seguido da sua área (ex: Autônomo - Designer).
')
                                ->toolbarButtons([
                                    'bold',
                                    'italic',
                                    'bulletList',
                                    'orderedList',
                                    // Omitted 'attachFiles' so users cannot add images
                                ])
                                ->required()
                                ->live()
                                ->hidden(fn() => !$this->externo),




                            Toggle::make('deficiente')
                                ->label('Pessoa com Deficiência')
                                ->default(false)
                                ->columnSpanFull(),

                            Repeater::make('pcd')
                                ->label(' Deficiências')
                                ->schema([

                                    Select::make('tipo')
                                        ->label('Tipo de Deficiência')
                                        ->options(function () {
                                            return TipoDeficiencia::all()->pluck('descricao', 'id');
                                        })
                                        ->required()
                                        ->live(),

                                    Textarea::make('descricao_necessidade')
                                        ->required()
                                        ->live(false)
                                        ->label('Descrição / Necessidades'),
                                ])
                                ->collapsible()
                                ->required()
                                ->hidden(fn(Get $get) => !$get('deficiente'))
                                ->itemLabel(function ($state) {
                                    $deficiencia = TipoDeficiencia::find($state['tipo']);
                                    if ($deficiencia) {
                                        return $deficiencia->codigo;
                                    }
                                })
                                ->collapsed()
                                ->default([]),


                        ])
                        ->live()
                        ->hidden(fn(Get $get) => $get('validado') == false)
                        ->columns(1),

                    Section::make('Termos')
                        ->label('Termos de Uso e Política de Privacidade')
                        ->schema([
                            Checkbox::make('concordo')
                                ->required()
                                ->hint('Ao realizar esta inscrição e/ou registrar frequência, autorizo o uso de minha imagem e
                                    voz em fotos e vídeos captados durante o evento para fins de divulgação
                                    institucional,
                                    materiais educativos e redes sociais.')
                                ->hintColor('warning')
                                ->live()
                        ])
                        ->hidden(fn(Get $get) => $get('validado') == false),
                ]
            )
            ->columns(4);
    }




    public function criarLogin($nomeCompleto): string
    {
        $partes = explode(' ', trim($nomeCompleto));

        if (count($partes) === 1) {
            return $partes[0]; // só tem um nome
        }
        $cod = rand(0, 99);
        $primeiro = $partes[0];
        $ultimo = $partes[count($partes) - 1];
        return strtolower($primeiro . '.' . $ultimo . $cod);
    }



    public function render()
    {
        return view('livewire.inscricao');
    }

    public function submit()
    {

        $this->form->validate();

        try {
            $data = $this->form->getState();

            logger()->info('Inscricao::submit');
            logger()->info($data);


            $cpf = preg_replace("/[^0-9]/", "", $data['cpf'] ?? null);
            if (!$cpf) {
                Notification::make()
                    ->title('CPF inválido!')
                    ->danger()
                    ->send();
                return;
            }
            $user = User::where('cpf', $cpf)->first();

            if (!$user) {

                $user = new User();
                $user->cpf = $cpf;
                $user->email = $this->usuario['email'] ?? $data['email'];
                $user->name = $this->usuario['name'] ?? $data['nome'];
                $user->telefone = $this->usuario['telefone'] ?? $data['telefone'];
                $user->password = Hash::make($data['password']);
                $user->login = self::criarLogin($user->name);
            } else {
                $aluno = CursoAlunos::where('aluno_id', $user->id)->where('curso_id', $this->curso->id)->first();

                if ($aluno) {
                    if ($this->curso->temAtividades()) {
                        $atividadesId = $this->curso->atividades()->pluck('id');
                        $alunos = CursoAlunos::where('aluno_id', $user->id)->whereIn('curso_id', $atividadesId)->get()->count();

                        if ($alunos >= count($atividadesId)) {
                            Notification::make()
                                ->title('A sua inscrição já foi realizada para este curso e suas atividades!')
                                ->warning()
                                ->persistent()
                                ->send();
                            return;
                        }
                    } else {

                        Notification::make()
                            ->title('A sua inscrição já foi realizada para este curso!')
                            ->warning()
                            ->persistent()
                            ->send();
                        return;
                    }
                }
            }
            // $user->cpf = $cpf;
            $name = $this->usuario['name'] ?? null;
            if (!$name || $name == '') {
                $name = $data['nome'];
            }
            $data['nome'] = $name;


            $email = $this->usuario['email'] ?? null;
            if (!$email || $email == '') {
                $email = $data['email'];
            }

            if ($data['externo']) {
                $data['tipouser'] = 2106;
            }
            $data['email'] = $email;
            $data['setor_orgao'] = $data['setor_orgao'] ?? null;
            $data['telefone'] = $this->usuario['telefone'] ?? $data['telefone'];
            $data['tipouser'] = $data['tipouser'] ?? 2106;
            $data['setor'] = $data['setor'] ?? 1029;
            $data['pcd'] = $data['deficiente'] ? $data['pcd'] : null;



            DB::beginTransaction();
            $this->save($user, $data);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $admins = User::role('super_admin')->get();

            Notification::make()
                ->title('Ocorreu um erro inesperado')
                ->danger()
                ->send();

            Notification::make()
                ->title('Erro no formulario de inscrição')
                ->body(' gerou uma exceção ao tentar se inscrever no curso: ' . $this->curso->titulo . ' No arquivo: ' . $e->getFile() . ' Linha: ' . $e->getLine() . ' ERRO: ' . $e->getMessage())
                ->sendToDatabase($admins);
            return;
        }
    }

    public function save($user, $data)
    {




        $user = $user->updateOrCreate(
            [
                'cpf' => $user->cpf
            ],
            [
                'cpf' => $user->cpf,
                'login' => $user->login,
                'name' => $user->name,
                'email' => $user->email,
                'telefone' => $user->telefone,
                'password' => $user->password,
            ]
        );

        $user->perfil()->updateOrCreate(
            [
                'user_id' => $user->id
            ],
            [
                'nome_social' => $data['nome_social'],
                'sexo' => $data['sexo'],
                'setor_orgao' => $data['setor_orgao'],
                'genero' => $data['genero'],
                'tipopessoa_id' => $data['tipouser'],
                'raca' => $data['raca'],
                'pcd' => $data['pcd'],
            ]
        );
        $user->lotacao()->updateOrCreate(
            [
                'user_id' => $user->id,
                'principal' => true
            ],
            [
                'setor_id' => $data['setor'],
                'atualizado_por' => $user->id,
                'principal' => true
            ]
        );

        if (count($data['atividades'] ?? []) > 0) {
            $atividades = CursoAtividades::whereIn('id', $data['atividades'])->get();
            if ($this->tipouser == 2104) {
                foreach ($atividades as $atividade) {
                    InscricaoService::inscrever($atividade, [$user], $data['setor_orgao']);
                }
            } else {
                foreach ($atividades as $atividade) {
                    InscricaoService::preInscrever($atividade, [$user], $data['setor_orgao']);
                }
            }
        } else {
            if ($this->tipouser == 2104) {
                InscricaoService::inscrever($this->curso, [$user], $data['setor_orgao']);
            } else {
                InscricaoService::preInscrever($this->curso, [$user], $data['setor_orgao']);
            }
        }
        $this->form->fill();
        $this->usuario = null;
        $this->tipouser = null;
        $this->validado = false;
    }

    public function pesquisarUsuario($cpf, Set $set)
    {
        if (!$cpf) {
            return;
        }
        $this->form->fill();
        $set('cpf', $cpf);
        $cpf = preg_replace("/[^0-9]/", "", $cpf ?? null);

        // Log::info('fill 3 user: ' . $this->usuario);
        $this->usuario = null;
        $this->tipouser = null;




        $user = User::where('cpf', $cpf)->get()->first();
        $set('validado', true);
        if ($user) {
            $this->usuario['name'] = $user->name;
            $this->usuario['email'] = $user->email;
            $this->usuario['telefone'] = $user->telefone;
            $this->usuario['password'] = 1;

            $aluno = CursoAlunos::where('aluno_id', $user->id)->where('curso_id', $this->curso->id)->first();

            if ($aluno) {

                if ($this->curso->temAtividades()) {
                    $atividadesId = $this->curso->atividades()->pluck('id');
                    $alunos = CursoAlunos::where('aluno_id', $user->id)->whereIn('curso_id', $atividadesId)->get()->count();

                    if ($alunos >= count($atividadesId)) {
                        Notification::make()
                            ->title('A sua inscrição já foi realizada para este curso e suas atividades!')
                            ->warning()
                            ->persistent()
                            ->send();
                        $set('validado', false);
                        return;
                    }
                } else {

                    Notification::make()
                        ->title('A sua inscrição já foi realizada para este curso!')
                        ->warning()
                        ->persistent()
                        ->send();
                    $set('validado', false);
                    return;
                }
            }
            logger()->info('Usuario encontrado: ' . $user->name);

            Notification::make()
                ->title('Seus dados foram encontrados em nossa base de dados!')
                ->success()
                ->send();


            $set('nome', $this->ofuscar($user->name, ' '));

            $set('email', $this->ofuscar($user->email, '@'));
            if ($user->telefone) {
                $set('telefone', $this->ofuscar($user->telefone, 'telefone'));
            } else {
                $set('telefone', $user->telefone);
            }
            $set('sexo', $user->perfil->sexo ?? '');

            $set('genero', $user->perfil->genero ?? '');

            $set('raca', $user->perfil->raca ?? '');

            $set('setor_orgao', $user->perfil->setor_orgao ?? '');

            $setor = $user->setorPrincipal()->first();
            if ($setor) {
                $this->usuario['setor'] = $setor->id;
                $set('setor', $setor->id);
            } else {
                $set('setor', null);
            }

            if ($user->perfil && is_array($user->perfil->pcd)) {
                $set('deficiente', count($user->perfil->pcd ?? []) > 0 ? true : false);
                $set('pcd', $user->perfil->pcd ?? []);
            } else {
                $set('deficiente', false);
                $set('pcd', []);
            }


            $this->tipouser = $user->perfil->tipopessoa_id ?? null;


            if ($this->tipouser && $this->tipouser != 2106) {
                $set('tipouser', $this->tipouser);
            }
            if ($this->tipouser == 2106) {
                $set('externo', true);
            } else {
                $set('externo', false);
            }

            return;
        } else {

            //Limpando os campos
            $set('email', null);
            $set('nome', null);
            $set('telefone', null);
            $set('nome_social', null);
            $set('orgao_setor', null);
            $set('sexo', null);
            $set('genero', null);
            $set('raca', null);
            $set('deficiente', false);
            $set('pcd', []);
            $set('setor', null);


            $recadastro = Recadastro::where('cpf', $cpf)->first();

            // dd($recadastro);
            if (!$recadastro) {
                logger()->info('Usuario não encontrado no recadastro');

                Notification::make()
                    ->title('Usuário não encontrado realize o cadastro para o registro da frequência!')
                    ->warning()
                    ->send();
                return;
            } else {

                if ($recadastro->email == null || $recadastro->email == '') {
                    $existente = false;
                    $this->usuario['email'] = null;
                } else {
                    $existente = User::where('email', $recadastro->email)->exists();
                    if ($existente) {
                        Notification::make()
                            ->title('O email encontrado já está em uso, por favor informe um email diferente para completar seu cadastro!')
                            ->warning()
                            ->send();
                        $this->usuario['email'] = null;
                    } else {
                        $this->usuario['email'] = $recadastro->email;
                    }
                }


                $this->usuario['cpf'] = $cpf;
                $this->usuario['name'] = $recadastro->nome;
                $this->usuario['telefone'] = $recadastro->telefone;
                $this->usuario['password'] = null;

                logger()->info('Usuario encontrado no recadastro: ' . $this->usuario['name']);
                Notification::make()
                    ->title('Seus dados foram encontrados em nossa base de dados, complete o cadastro para o registro da frequência!')
                    ->success()
                    ->send();



                $set('nome', $this->ofuscar($this->usuario['name'], ' '));
                if ($this->usuario['email']) {
                    $set('email', $this->ofuscar($this->usuario['email'], '@'));
                } else {
                    $set('email', $this->usuario['email']);
                }
                if ($this->usuario['telefone']) {
                    $set('telefone', $this->ofuscar($this->usuario['telefone'], 'telefone'));
                } else {
                    $set('telefone', $this->usuario['telefone'], 'telefone');
                }
                return;
            }
        }
    }



    function limparString($string)
    {
        $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
        $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string);
        $string = preg_replace('/-+/', '-', $string);
        $string = trim($string, '-');

        return $string;
    }

    public function ofuscar(string $str, string $separator): string
    {
        $str = $this->limparString($str);
        $result = [];
        if ($separator == 'telefone') {
            $x = str_repeat('*', 5);
            $result = $x . substr($str, -4);
            return $result;
        }
        if ($separator != 'N') {
            $s = explode($separator, $str);
            foreach ($s as $v) {
                $x = substr_replace($v, '*', 3);
                $t = strlen($v);
                if ($t < 3)
                    $t = 4;
                $result[] = $x . str_repeat('*', $t - 3);
            }
            $result = implode($separator, $result);
        } else {
            $x = str_repeat('*', 5);
            $result = $x . substr($str, -4);
        }

        return $result;
    }
}
