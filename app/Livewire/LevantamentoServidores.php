<?php

namespace App\Livewire;

use App\Models\Administrativo\Demanda;
use Exception;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Notifications\Notification;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Livewire\Component;
use Filament\Forms\Components\TextInput;
use App\Models\Escola\CursoAlunos;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\Cursos;
use App\Models\Escola\TipoDeficiencia;
use App\Models\Escola\TipoGenero;
use App\Models\Escola\TipoPublico;
use App\Models\Escola\TipoRaca;
use App\Models\Escola\TipoSexo;
use App\Models\Recadastro;
use App\Models\Setores;
use App\Models\Taggable;
use App\Models\User;
use App\Services\EmailService;
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


class LevantamentoServidores extends Component implements HasForms, HasActions

{
    use InteractsWithActions;
    use InteractsWithForms;

    public array $data = [];

    public $tipouser;
    public $tiponome;
    public $usuario;
    public $cabecalho;
    public $concordo;
    public $numero_servidores;
    public $telefone_setor;
    public $cpf;
    public $nome;
    public $indicacao;
    public $justificativa;
    public $ano;
    public $perfil_publico;
    public $setor;
    public $password;
    public $passwordconfirm;
    public $novouser = false;
    public $email;
    public $telefone;
    public $validado = false;


    public bool $disponivel;


    public function mount(): void
    {
        if (config('settings.demandas.levantamento') == true) {
            $this->cabecalho = 'Levantamento de Necessidades de Treinamento' . ' Servidoras e Servidores';
            $this->disponivel = true;
            $this->ano = Carbon::now()->year;
        } else {
            $this->disponivel = false;
        }
    }

    public static function validarVinculo(int|null $v)
    {
        if ($v == 2108 || ($v <= 2204 && $v >= 2201) || !$v) {
            return true;
        } else {
            return false;
        }
    }

    public function form(Schema $schema): Schema
    {

        return $schema
            ->components(

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
                                ->inlineLabel()
                                ->disabled(function () {
                                    // dd($this->usuario);
                                    if ($this->usuario) {
                                        return $this->usuario['name'] ? true : false;
                                    }
                                    return false;
                                })
                                ->live()
                                ->maxLength(255)
                                ->columnSpanFull(),

                            // Section::make('E-mail')
                            //     ->schema([
                            TextInput::make('email')
                                ->label('E-mail')
                                ->inlineLabel()
                                ->helperText('Atenção utilizem seu e-mail institucional')
                                // ->hint('Atenção: Servidores, Estagiários e Magistrados, por favor, utilizem seu e-mail institucional do TJES para realizar a inscrição. Isso assegura a validação do seu vínculo e o recebimento de notificações oficiais.')
                                // ->hintColor('warning+.')
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
                            // ]),

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
                                ->inlineLabel()
                                ->disabled(function () {
                                    if ($this->usuario) {
                                        return $this->usuario['telefone'] ? true : false;
                                    }
                                    return false;
                                })
                                ->required()
                                ->live()

                                // ->maxLength(15)
                                ->columnSpanFull(),


                            Select::make('tipouser')
                                ->label('Vinculo')
                                ->options(function () {
                                    return TipoPublico::whereIn('id', [2104, 2201, 2202, 2203, 2204, 2108])->pluck('descricao', 'id');
                                })
                                ->required()
                                ->columnSpanFull()
                                ->visible(fn() => !($this->usuario && $this->usuario['password'])),



                            Select::make('setor')
                                ->label('Setor')
                                ->live()
                                ->searchable()
                                ->options(function () {
                                    return Setores::all()->pluck('setor', 'id');
                                })
                                ->required()

                                ->columnSpanFull(),

                        ])
                        ->live()
                        ->hidden(fn(Get $get) => $get('validado') == false)
                        ->columns(1)
                        ->columnSpanFull(),

                    Section::make('Demanda')
                        ->schema([
                            TextInput::make('numero_servidores')
                                ->label('Número de servidoras e servidores atualmente no setor ou na unidade')
                                ->required(),

                            TextInput::make('telefone_setor')
                                ->label('Telefone(s) do setor ou da unidade')
                                ->required(),

                            TextArea::make('indicacao')
                                ->rows(10)
                                ->label('Indicação de curso(s) necessário(s)')
                                ->required(),

                            TextArea::make('justificativa')
                                ->rows(10)
                                ->label('Justificativa da necessidade do(s) curso(s) indicados')
                                ->required(),

                            TextArea::make('perfil_publico')
                                ->rows(10)
                                ->label('Perfil do público a ser atendido pelo(s) curso(s) indicado(s)')
                                ->required(),
                        ])
                        ->hidden(fn(Get $get) => $get('validado') == false)
                        ->columnSpanFull(),



                    Section::make('Termos')
                        ->label('Termos de Uso e Política de Privacidade')
                        ->schema([
                            Checkbox::make('concordo')
                                ->required()
                                ->hint('Confirmo que os dados cadastrados são verdadeiros')
                                ->hintColor('warning')
                                ->live()
                        ])
                        ->hidden(fn(Get $get) => $get('validado') == false)
                        ->columnSpanFull(),
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
        return view('livewire.levantamento');
    }

    public function submit()
    {

        $this->form->validate();

        try {
            $data = $this->form->getState();

            logger()->info('Levantamento::submit');
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
            }

            if ($this->usuario['name'] ?? null && $this->usuario['name'] != '') {
                $data['nome'] = $this->usuario['name'];
            }

            if ($this->usuario['email'] ?? null && $this->usuario['email'] != '') {
                $data['email'] = $this->usuario['email'];
            }

            $data['telefone'] = $this->usuario['telefone'] ?? $data['telefone'];
            $data['tipouser'] = $this->tipouser ?? $data['tipouser'];
            $data['setor'] = $data['setor'] ?? 1029;



            DB::beginTransaction();
            $this->save($user, $data);
            DB::commit();
            Notification::make()
                ->title('Levantamento de necessidades enviado com sucesso!')
                ->success()
                ->send();
        } catch (Exception $e) {
            DB::rollBack();
            $admins = User::role('super_admin')->get();

            Notification::make()
                ->title('Ocorreu um erro inesperado')
                ->danger()
                ->send();

            Notification::make()
                ->title('Erro no formulario de inscrição')
                ->body(' gerou uma exceção No arquivo: ' . $e->getFile() . ' Linha: ' . $e->getLine() . ' ERRO: ' . $e->getMessage())
                ->sendToDatabase($admins);
            return;
        }
    }

    public function save(User $user, array $data)
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
                'tipopessoa_id' => $data['tipouser'],
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

        $demanda = new Demanda();
        $demanda->indicado_por = $user->id;
        $demanda->setor_id = $data['setor'];
        $demanda->tipodemanda_id = 3100;
        $ano = Carbon::now()->year;
        $demanda->titulo = "Levantamento de Necessidades - {$ano}";

        //montar a descricao da demanda que será exibida em um rich editor, o campo descrição deve juntar os 3 campos no formulario, agrupando por curso indicado, justificativa e perfil do publico
        $descricao = "<h2>Curso(s) Indicado(s)</h2>";
        $descricao .= "<p>{$data['indicacao']}</p>";
        $descricao .= "<h2>Justificativa</h2>";
        $descricao .= "<p>{$data['justificativa']}</p>";
        $descricao .= "<h2>Perfil do Público</h2>";
        $descricao .= "<p>{$data['perfil_publico']}</p>";
        $descricao .= "<h2>Número de servidores no setor ou unidade</h2>";
        $descricao .= "<p>{$data['numero_servidores']}</p>";
        $descricao .= "<h2>Telefone(s) do setor ou unidade</h2>";
        $descricao .= "<p>{$data['telefone_setor']}</p>";
        $demanda->ativo = true;
        $demanda->descricao = $descricao;
        $demanda->save();
        Taggable::create([
            'tag_id' => 99,
            'taggable_type' => Demanda::class,
            'taggable_id' => $demanda->id,
        ]);




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

            $setor = $user->setorPrincipal()->first();
            if ($setor) {
                $this->usuario['setor'] = $setor->id;
                $set('setor', $setor->id);
            } else {
                $set('setor', null);
            }


            $vinculo_valido = self::validarVinculo($user->perfil->tipopessoa_id ?? null);
            $this->tipouser = $user->perfil->tipopessoa_id ?? null;
            if ($vinculo_valido) {
                $set('tipouser', $this->tipouser);
            } else {
                $set('tipouser', null);
                $this->validado = false;
                $set('validado', false);
                Notification::make()
                    ->title('Preenchimento exclusivo a servidores')
                    ->warning()
                    ->send();
                return;
            }


            return;
        } else {

            //Limpando os campos
            $set('email', null);
            $set('nome', null);
            $set('telefone', null);
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
