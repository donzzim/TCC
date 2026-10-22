<?php

namespace App\Filament\App\Pages;

use App\Models\Escola\TipoDeficiencia;
use App\Models\Escola\TipoTitulacao;
use App\Models\Perfil;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Http;

class ResgistrarFormador extends Page implements HasForms
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationLabel = 'Registrar como Formador';

    protected static ?string $modelLabel = 'Perfil ';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.app.pages.resgistrar-formador';

    public ?string $user_name = null;

    public ?Perfil $perfil = null;

    public $endereco = null;

    public $viewTitulacao = null;

    public $titulacao = null;

    public $pis_pasep_nit = null;

    public $categoria_profissional = null;

    public $cargo = null;

    public $pcd = null;

    public $cbo = null;

    public $idioma = null;

    public $curriculo = null;

    public $contato = null;

    public $estrangeiro = false;

    public $nova_area_de_atuacao = null;

    public $novo_nivel = null;

    public function mount(): void
    {
        $this->perfil = Perfil::firstOrNew(['user_id' => Filament::auth()->id()]);
        // dd($this->perfil);
        $user = Filament::auth()->user();
        $this->user_name = $user instanceof User ? $user->name : null;

        $this->titulacao = $this->perfil->titulacao ?? [];
        foreach ($this->titulacao as $area => $nivel) {
            $descricao = TipoTitulacao::query()->whereKey($nivel)->value('descricao');
            $this->viewTitulacao[$area] = is_string($descricao) ? $descricao : 'N/D';
        }
        // dd($this->titulacao);
        $this->pis_pasep_nit = $this->perfil->pis_pasep_nit;
        $this->categoria_profissional = $this->perfil->categoria_profissional;
        $this->cargo = $this->perfil->cargo;
        $this->contato = $this->perfil->contato;
        $this->cbo = $this->perfil->cbo ?? [];
        $this->pcd = $this->perfil->pcd ?? [];
        $this->idioma = $this->perfil->idioma ?? [];
        $this->estrangeiro = $this->perfil->estrangeiro;
        $this->curriculo = $this->perfil->curriculo ?? null;
        $this->endereco = $this->perfil->endereco ?? [
            'cep' => null,
            'logradouro' => null,
            'complemento' => null,
            'bairro' => null,
            'localidade' => null,
            'uf' => null,
            'estado' => null,
        ];
    }

    public static function getFormfieldsDados()
    {
        return
            Section::make('Dados Pessoais')
                ->icon('heroicon-o-user')
                ->compact()
                ->schema([

                    Forms\Components\TextInput::make('user_name')
                        ->required()
                        ->columnSpan([
                            'sm' => 4,
                            'xl' => 1,
                        ])
                        ->label('Nome Completo'),

                    Forms\Components\TextInput::make('pis_pasep_nit')
                        ->columnSpan([
                            'sm' => 4,
                            'xl' => 1,
                        ])
                        ->label('PIS/PASEP/NIT')
                        ->maxLength(11)
                        ->mask('99999999999'),

                    Forms\Components\TextInput::make('categoria_profissional')
                        ->required()
                        ->columnSpan([
                            'sm' => 4,
                            'xl' => 1,
                        ])
                        ->label('Categoria Profissional'),

                    Forms\Components\TextInput::make('cargo')
                        ->required()
                        ->columnSpan([
                            'sm' => 4,
                            'xl' => 1,
                        ])
                        ->label('Cargo'),

                    Forms\Components\Repeater::make('cbo')
                        ->label(' CBO')
                        ->schema([
                            Forms\Components\TextInput::make('codigo')
                                ->label('Código')
                                ->required()
                                ->live()
                                ->mask('9999-99'),
                            Forms\Components\TextInput::make('descricao')
                                ->label('Descrição')
                                ->required(),
                        ])
                        ->collapsible()
                        ->itemLabel(function ($state) {
                            return $state['codigo'] ?? '';
                        })
                        ->default([]),

                    Forms\Components\TextInput::make('contato')
                        ->required()
                        ->columnSpan(2)
                        ->tel()
                        ->maxLength(20)
                        ->mask('(99) 99999-9999')
                        ->rule('regex:/^\(?\d{2}\)?[\s-]?\d{4,5}-?\d{4}$/')
                        ->label('Contato'),

                    Forms\Components\Checkbox::make('estrangeiro')
                        ->default(false)
                        ->inline(true)
                        ->reactive()
                        ->label('Estrangeiro'),

                ]);
    }

    public static function getFormfieldsFormacao()
    {
        return Section::make('Formacao')
            ->icon('heroicon-o-academic-cap')
            ->compact()
            ->schema([

                Forms\Components\Select::make('nova_area_de_atuacao')
                    ->label('Área de Atuação')
                    ->searchable()
                    ->options([
                        'Administração' => 'Administração',
                        'Agronomia' => 'Agronomia',
                        'Análise e Desenvolvimento de Sistemas' => 'Análise e Desenvolvimento de Sistemas',
                        'Arquitetura e Urbanismo' => 'Arquitetura e Urbanismo',
                        'Artes Visuais' => 'Artes Visuais',
                        'Biologia' => 'Biologia',
                        'Ciência da Computação' => 'Ciência da Computação',
                        'Ciências Contábeis' => 'Ciências Contábeis',
                        'Ciências Econômicas' => 'Ciências Econômicas',
                        'Direito' => 'Direito',
                        'Educação Física' => 'Educação Física',
                        'Enfermagem' => 'Enfermagem',
                        'Engenharia Civil' => 'Engenharia Civil',
                        'Engenharia de Produção' => 'Engenharia de Produção',
                        'Engenharia Elétrica' => 'Engenharia Elétrica',
                        'Engenharia Mecânica' => 'Engenharia Mecânica',
                        'Filosofia' => 'Filosofia',
                        'Física' => 'Física',
                        'Geografia' => 'Geografia',
                        'História' => 'História',
                        'Letras - Português' => 'Letras - Português',
                        'Letras - Inglês' => 'Letras - Inglês',
                        'Matemática' => 'Matemática',
                        'Medicina' => 'Medicina',
                        'Nutrição' => 'Nutrição',
                        'Pedagogia' => 'Pedagogia',
                        'Psicologia' => 'Psicologia',
                        'Química' => 'Química',
                        'Recursos Humanos' => 'Recursos Humanos',
                        'Redes de Computadores' => 'Redes de Computadores',
                        'Sociologia' => 'Sociologia',
                        'Serviço Social' => 'Serviço Social',
                        'Tecnologia da Informação' => 'Tecnologia da Informação',
                        'Turismo' => 'Turismo',
                    ])
                    ->required()
                    ->columnSpan(2),

                Forms\Components\Select::make('novo_nivel')
                    ->options(
                        function () {
                            return TipoTitulacao::pluck('descricao', 'id');
                        }
                    )
                    ->searchable()
                    ->label('Titulação')
                    ->preload()
                    ->live()
                    ->required()
                    ->columnSpan(2),

            ]);
    }

    public static function getFormfieldsCurriculo()
    {
        return Section::make('Curriculo')
            ->icon('heroicon-o-document-text')
            ->compact()
            ->schema([
                Forms\Components\RichEditor::make('curriculo')
                    ->required()
                    ->label('Resumo'),

                Forms\Components\Repeater::make('pcd')
                    ->label('Deficiências')
                    ->schema([
                        Forms\Components\Select::make('codigo')
                            ->options(function () {
                                return TipoDeficiencia::all()->pluck('codigo', 'id');
                            })
                            ->required()
                            ->live(),

                    ])
                    ->collapsible()
                    ->itemLabel(function ($state) {
                        return $state['codigo'] ?? '';
                    })
                    ->default([]),
                Forms\Components\Repeater::make('idioma')
                    ->label(' Idiomas')
                    ->schema([
                        Forms\Components\Select::make('lingua')
                            ->label('Língua')
                            ->required()
                            ->options(
                                collect([
                                    'Português' => 'Português',
                                    'Inglês' => 'Inglês',
                                    'Espanhol' => 'Espanhol',
                                    'Francês' => 'Francês',
                                    'Alemão' => 'Alemão',
                                    'Italiano' => 'Italiano',
                                    'Mandarim' => 'Mandarim',
                                    'Japonês' => 'Japonês',
                                    'Russo' => 'Russo',
                                    'Árabe' => 'Árabe',
                                    'Hebraico' => 'Hebraico',
                                    'Grego' => 'Grego',
                                    'Latim' => 'Latim',
                                    'Holandês' => 'Holandês',
                                    'Sueco' => 'Sueco',
                                    'Norueguês' => 'Norueguês',
                                    'Dinamarquês' => 'Dinamarquês',
                                    'Finlandês' => 'Finlandês',
                                    'Polonês' => 'Polonês',
                                    'Tcheco' => 'Tcheco',
                                    'Húngaro' => 'Húngaro',
                                    'Turco' => 'Turco',
                                    'Romeno' => 'Romeno',
                                    'Búlgaro' => 'Búlgaro',
                                    'Ucraniano' => 'Ucraniano',
                                    'Sérvio' => 'Sérvio',
                                    'Croata' => 'Croata',
                                    'Eslovaco' => 'Eslovaco',
                                    'Esloveno' => 'Esloveno',
                                    'Hindi' => 'Hindi',
                                    'Bengali' => 'Bengali',
                                    'Urdu' => 'Urdu',
                                    'Tâmil' => 'Tâmil',
                                    'Tailandês' => 'Tailandês',
                                    'Vietnamita' => 'Vietnamita',
                                    'Coreano' => 'Coreano',
                                    'Indonésio' => 'Indonésio',
                                    'Malaio' => 'Malaio',
                                    'Filipino' => 'Filipino',
                                    'Suaíli' => 'Suaíli',
                                    'Zulu' => 'Zulu',
                                    'Africâner' => 'Africâner',
                                    'Outro' => 'Outro',
                                ])->sort()->toArray()
                            )
                            ->searchable(),
                        Forms\Components\Select::make('nivel')
                            ->label('Nível')
                            ->required()
                            ->options([
                                'Básico' => 'Básico',
                                'Intermediário' => 'Intermediário',
                                'Avançado' => 'Avançado',
                                'Fluente' => 'Fluente',
                                'Nativo' => 'Nativo',
                            ])
                            ->searchable(),
                    ])
                    ->collapsible()
                    ->itemLabel(fn ($state) => $state['lingua'] ?? '')
                    ->default([]),
            ]);
    }

    public static function getFormfieldsCep(string $description)
    {
        return Section::make('Localização')
            ->icon('heroicon-m-map-pin')
            ->description($description)
            ->compact()
            ->schema([

                Group::make()->columns(4)->schema([
                    Forms\Components\TextInput::make('endereco.cep')
                        ->mask('99.999-999')
                        ->maxLength(255)
                        ->live()
                        ->columnSpan([
                            'sm' => 4,
                            'xl' => 1,
                        ])
                        ->suffixAction(
                            Action::make('buscarCep')
                                ->icon('heroicon-m-magnifying-glass')
                                ->requiresConfirmation(false)
                                ->action(function (Set $set, Get $get) {
                                    if (blank($get('endereco.cep'))) {
                                        Notification::make()
                                            ->title('Entre com o CEP')
                                            ->danger()->send();

                                        return;
                                    }

                                    try {
                                        $cep = preg_replace('/[^0-9]/', '', $get('endereco.cep'));
                                        // dd($cep);
                                        $cepData =
                                            Http::withOptions([
                                                'verify' => false,
                                                'timeout' => 20,
                                            ])
                                                ->get("https://viacep.com.br/ws/{$cep}/json")
                                                ->throw()
                                                ->json();

                                        if ($cepData) {
                                            $set('endereco.logradouro', $cepData['logradouro'] ?? null);
                                            $set('endereco.complemento', $cepData['complemento'] ?? null);
                                            $set('endereco.bairro', $cepData['bairro'] ?? null);
                                            $set('endereco.localidade', $cepData['localidade'] ?? null);
                                            $set('endereco.uf', $cepData['uf'] ?? null);
                                            $set('endereco.estado', $cepData['estado'] ?? null);
                                        } else {
                                            Notification::make()
                                                ->title('CEP não encontrado')
                                                ->danger()
                                                ->send();
                                        }
                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Erro ao consultar o CEP: '.$e->getMessage())
                                            ->danger()
                                            ->send();
                                    }
                                })
                        ),
                ]),
                Group::make()->columns(5)->schema([
                    Forms\Components\TextInput::make('endereco.logradouro')
                        ->label('Logradouro')
                        ->columnSpan([
                            'sm' => 5,
                            'xl' => 2,
                        ])
                        ->maxLength(255),
                    Forms\Components\TextInput::make('endereco.complemento')
                        ->label('Complemento')
                        ->columnSpan([
                            'sm' => 5,
                            'xl' => 1,
                        ])
                        ->maxLength(255),
                    Forms\Components\TextInput::make('endereco.bairro')
                        ->label('Bairro')
                        ->columnSpan([
                            'sm' => 5,
                            'xl' => 2,
                        ])
                        ->maxLength(255),
                ]),
                Group::make()->columns(5)->schema([
                    Forms\Components\TextInput::make('endereco.localidade')
                        ->label('Cidade')
                        ->columnSpan([
                            'sm' => 5,
                            'xl' => 2,
                        ])
                        ->maxLength(255),
                    Forms\Components\TextInput::make('endereco.uf')
                        ->label('UF')
                        ->columnSpan([
                            'sm' => 5,
                            'xl' => 1,
                        ])
                        ->maxLength(2),
                    Forms\Components\TextInput::make('endereco.estado')
                        ->label('Estado')
                        ->columnSpan([
                            'sm' => 5,
                            'xl' => 2,
                        ])
                        ->maxLength(255),
                ]),

            ])->columnSpanFull();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Registrar Formador')
                ->tabs([
                    Tabs\Tab::make('Dados Pessoais')
                        ->schema([
                            static::getFormfieldsDados(),
                        ]),
                    Tabs\Tab::make('Endereço')
                        ->schema([
                            static::getFormfieldsCep('endereco'),
                        ]),
                    Tabs\Tab::make('Formação')
                        ->schema([
                            static::getFormfieldsFormacao(),

                            \Filament\Schemas\Components\View::make('components.titulacoes-lista'),
                        ]),
                    Tabs\Tab::make('Curriculo')
                        ->schema([
                            static::getFormfieldsCurriculo(),
                        ]),
                ]),
        ])
            ->columns(1);
    }

    public function removerTitulacao($area)
    {
        unset($this->titulacao[$area]);
        unset($this->viewTitulacao[$area]);
    }

    public function adicionarTitulacao()
    {
        if ($this->novo_nivel && $this->nova_area_de_atuacao) {

            foreach ($this->titulacao as $area => $nivel) {
                if ($area === $this->nova_area_de_atuacao) {
                    Notification::make()
                        ->title("Área de atuação já cadastrada: {$area}")
                        ->warning()
                        ->send();

                    return;
                }
            }

            $this->titulacao[$this->nova_area_de_atuacao] = $this->novo_nivel;
            $descricao = TipoTitulacao::query()->whereKey($this->novo_nivel)->value('descricao');
            $this->viewTitulacao[$this->nova_area_de_atuacao] = is_string($descricao) ? $descricao : 'N/D';

            $this->novo_nivel = null;
            $this->nova_area_de_atuacao = null;
        }
    }

    public function save()
    {
        $user = User::findOrFail(Filament::auth()->id());
        $user->name = $this->user_name;
        $user->save();
        $this->adicionarTitulacao();

        $this->perfil->fill([
            'user_id' => $user->id,
            'titulacao' => $this->titulacao,
            'cbo' => $this->cbo,
            'pcd' => $this->pcd,
            'idioma' => $this->idioma,
            'curriculo' => $this->curriculo ?? null,
            'pis_pasep_nit' => $this->pis_pasep_nit,
            'categoria_profissional' => $this->categoria_profissional,
            'cargo' => $this->cargo,
            'contato' => $this->contato,
            'estrangeiro' => $this->estrangeiro,
            'endereco' => [
                'cep' => $this->endereco['cep'],
                'logradouro' => $this->endereco['logradouro'],
                'complemento' => $this->endereco['complemento'],
                'bairro' => $this->endereco['bairro'],
                'localidade' => $this->endereco['localidade'],
                'uf' => $this->endereco['uf'],
                'estado' => $this->endereco['estado'],
            ],
        ]);
        $this->perfil->save();

        Notification::make()
            ->title('Dados salvos com sucesso!')
            ->success()
            ->send();
    }
}
