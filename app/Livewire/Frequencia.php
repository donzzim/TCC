<?php

namespace App\Livewire;



use Exception;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Notifications\Notification;

use Illuminate\Support\Facades\Log;


use App\Services\Geolocalizacao\LocationService;
use Livewire\Attributes\On;

use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Livewire\Component;
use Filament\Forms\Components\TextInput;
use App\Models\Escola\CursoAlunos;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\Cursos;
use App\Models\Escola\TipoDeficiencia;
use App\Models\Escola\TipoGenero;
use App\Models\Escola\TipoLocal;
use App\Models\Escola\TipoPublico;
use App\Models\Escola\TipoRaca;
use App\Models\Escola\TipoSexo;
use App\Models\Recadastro;
use App\Models\Setores;
use App\Models\User;
use App\Services\EmailService;
use App\Services\Pedagogico\FrequenciaService;
use App\Services\Pedagogico\InscricaoService;
use Carbon\Carbon;
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

class Frequencia extends Component implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;


    public ?float $latitude = null;
    public ?float $longitude = null;
    public ?int $accuracy = null;
    public ?int $distancia = null;

    public bool $localizacaoCapturada = false;
    public bool $localizacaoValida = false;
    public ?string $mensagemLocalizacao = null;
    public $local;

    public array $data = [];
    public $uuid;
    public $tipouser;
    public $tiponome;
    public $usuario;
    public $concordo;
    public $cpf;
    public $sexo;
    public $aluno;
    public $agenda;
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
    public $frequencia;
    public $inicio;
    public $termino;
    public $curso;

    public $mensagem = '';


    public function mount($uuid): void
    {
        $this->form->fill();

        $this->uuid = $uuid;
        $row = Cursos::where('uuid', $uuid)->first();
        if (!$row) {
            $row = CursoAtividades::where('uuid', $uuid)->first();
        }
        $this->curso = $row;

        $agenda = FrequenciaService::class::getAgendaFrequencia($this->curso);

        if (!$agenda) {
            $this->disponivel = false;
            return;
        }

        $this->local = TipoLocal::find($agenda->local_id);
        $this->localizacaoValida = ! $this->localizacaoObrigatoria();
        $this->inicio = Carbon::parse($agenda->inicio)->format('d/m/Y H:i');
        $this->termino = Carbon::parse($agenda->termino)->format('d/m/Y H:i');

        $this->disponivel = true;
        $this->agenda = $agenda;
    }

    public function localizacaoObrigatoria(): bool
    {
        return LocationService::shouldValidateProximity($this->local);
    }

    public function podeRegistrarFrequencia(): bool
    {
        return $this->formularioValidado()
            && (! $this->localizacaoObrigatoria() || $this->localizacaoValida);
    }

    public function formularioValidado(): bool
    {
        return (bool) data_get($this->data, 'validado', false);
    }


    #[On('locationCaptured')]
    public function locationCaptured($lat, $lng, $accuracy): void
    {
        if (! $this->localizacaoObrigatoria()) {
            $this->localizacaoCapturada = true;
            $this->localizacaoValida = true;
            $this->mensagemLocalizacao = 'Validacao de localizacao dispensada.';
            return;
        }

        $this->latitude = (float) $lat;
        $this->longitude = (float) $lng;
        $this->accuracy = (int) $accuracy;
        $this->localizacaoCapturada = true;

        $result = LocationService::validateProximity(
            userLat: $this->latitude,
            userLng: $this->longitude,
            accuracy: $this->accuracy,
            targetLat: data_get($this->local, 'json.latitude'),
            targetLng: data_get($this->local, 'json.longitude'),
            radiusMeters: config('settings.localizacao.raio'),
            maxAccuracy: 100,
        );

        $this->localizacaoValida = $result['allowed'];
        $this->mensagemLocalizacao = $result['message'];
        $this->distancia = $result['distance'];

        if (! $this->localizacaoValida) {
            Notification::make()
                ->title('Localização inválida')
                ->body($this->mensagemLocalizacao)
                ->danger()
                ->send();
        }
    }

    public function form(Schema $schema): Schema
    {

        return $schema
            ->components(

                [

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
                        // ->extraAttributes([
                        //     'wire:keydown.enter.prevent' => "mountFormComponentAction('cpf', 'pesquisar')",
                        // ])
                        ->maxLength(14)
                        ->columnSpanFull(),
                    Section::make('cadastro')
                        ->schema([
                            TextInput::make('nome')
                                ->label('Nome Completo')
                                ->required()
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

                            Toggle::make('externo')
                                ->label('Publico Externo')
                                ->default(false)
                                ->live(),

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
                                ->hidden(fn(Get $get) => $get('externo'))
                                ->disabled(fn(Get $get) => $get('externo')),



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
                                ->hidden(fn(Get $get) => $get('externo'))
                                ->disabled(fn(Get $get) => $get('externo'))
                                ->columnSpanFull(),

                            Textarea::make('setor_orgao')
                                ->label('Vínculo empregaticío')
                                ->required()
                                ->live()
                                ->hidden(fn(Get $get) => ! $get('externo')),



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

                                    RichEditor::make('descricao_necessidade')
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
                        ->columns(1)
                        ->columnSpanFull(),

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
                        ->hidden(fn(Get $get) => $get('validado') == false)
                        ->columnSpanFull(),
                ]
            )
            ->columns(4)
            ->statePath('data');
    }


    public static function criarLogin($nomeCompleto): string
    {
        $partes = explode(' ', trim($nomeCompleto));

        $cod = rand(0, 99);
        if (count($partes) === 1) {
            return strtolower($partes[0] . '.' . $cod);
        }
        $primeiro = $partes[0];
        $ultimo = $partes[count($partes) - 1];
        return strtolower($primeiro . '.' . $ultimo . $cod);
    }


    public function render()
    {
        return view('livewire.frequencia');
    }

    public function submit()
    {

        $this->form->validate();

        if ($this->localizacaoObrigatoria() && ! $this->localizacaoValida) {
            Notification::make()
                ->title('Valide sua localizacao para registrar a frequencia.')
                ->danger()
                ->send();

            return;
        }

        try {
            $data = $this->form->getState();

            Log::info('frequencia::save');
            Log::info($data);

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

                $freq = $user->frequencias()
                    ->where('curso_id', $this->curso->id)
                    ->where('agenda_id', $this->agenda->id)
                    ->where('situacaofrequencia_id', config('settings.frequencia.presente'))
                    ->first();

                if ($freq) {
                    Notification::make()
                        ->title('Frequência já registrada!')
                        ->warning()
                        ->send();
                    return;
                }
            }

            $name = $this->usuario['name'] ?? null;
            if (!$name || $name == '') {
                $name = $data['nome'];
            }
            $data['nome'] = $name;


            $email = $this->usuario['email'] ?? null;
            if (!$email || $email == '') {
                $email = $data['email'];
            }
            $data['email'] = $email;
            $data['setor_orgao'] = $data['setor_orgao'] ?? null;
            $data['telefone'] = $this->usuario['telefone'] ?? $data['telefone'];
            $data['tipouser'] = $data['tipouser'] ?? 2101;
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
                ->title('Erro no formulario de frequencia')
                ->body($data['nome'] . ' gerou uma exceção ao tentar registrar frequencia no curso: ' . $this->curso->titulo . ' No arquivo: ' . $e->getFile() . ' Linha: ' . $e->getLine() . ' ERRO: ' . $e->getMessage())
                ->sendToDatabase($admins);
            return;
        }
    }

    public function save($user, $data)
    {
        // dd($data);


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

        $aluno = CursoAlunos::where('curso_id', $this->agenda->agendaable_id)->where('aluno_id', $user->id)->where('situacaoinscricao_id', config('settings.inscricao.inscrito'))->first();
        if (!$aluno) {
            InscricaoService::inscrever($this->agenda->agendaable, [$user], $data['setor_orgao']);
        }

        $this->frequencia = $user->frequencias()->where('agenda_id', $this->agenda->id)
            ->first();

        if ($this->frequencia) {
            $this->frequencia->presente($user);
            Notification::make()
                ->title('Frequência registrada com sucesso!')
                ->success()
                ->send();

            //limpar o formulario;

            $this->form->fill();
            $this->usuario = null;
            $this->aluno = null;
            $this->tipouser = null;
            $this->validado = false;
            $this->data['validado'] = false;
        } else {
            Notification::make()
                ->title('Erro ao registrar a frequência!')
                ->body('Entre em contato com a administração do curso!')
                ->danger()
                ->send();
        }
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
        $this->aluno = null;
        $this->tipouser = null;




        $user = User::where('cpf', $cpf)->get()->first();
        $set('validado', true);

        if ($user) {
            $this->usuario['name'] = $user->name;
            $this->usuario['email'] = $user->email;
            $this->usuario['telefone'] = $user->telefone;
            $this->usuario['password'] = 1;

            $freq = $user->frequencias()
                ->where('curso_id', $this->agenda->agendaable_id)
                ->where('agenda_id', $this->agenda->id)
                ->where('situacaofrequencia_id', config('settings.frequencia.presente'))
                ->first();

            if ($freq) {
                Notification::make()
                    ->title('Frequência já registrada!')
                    ->warning()
                    ->send();
                $set('validado', false);
                return;
            }
            $this->aluno = $this->curso->alunos()->where('aluno_id', $user->id)->first();
            if ($this->aluno) {
                if ($this->aluno->situacaoinscricao_id == config('settings.inscricao.inscrito')) {

                    Log::info('Aluno encontrado: ' . $user->name);
                    Notification::make()
                        ->title('Aluno encontrado')
                        ->success()
                        ->send();
                } else {
                    Log::info('Aluno não inscrito no curso: ' . $user->name);
                    Notification::make()
                        ->title('Aluno Pré Inscrito no curso')
                        ->body('registre a frequencia para ser inscrito!')
                        ->warning()
                        ->send();
                }
            } else {
                Log::info('Aluno não inscrito no curso: ' . $user->name);
                Notification::make()
                    ->title('Aluno não inscrito no curso')
                    ->body('registre a frequencia para ser inscrito!')
                    ->warning()
                    ->send();
            }


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
        } else {

            //Limpando os campos
            $set('email', null);
            $set('nome', null);
            $set('telefone', null);
            $set('nome_social', null);
            $set('sexo', null);
            $set('genero', null);
            $set('raca', null);
            $set('deficiente', false);
            $set('pcd', []);
            $set('setor', null);


            $recadastro = Recadastro::where('cpf', $cpf)->first();

            // dd($recadastro);
            if (!$recadastro) {
                Log::info('Usuario não encontrado no recadastro');

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

                Log::info('Usuario encontrado no recadastro: ' . $this->usuario['name']);
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
