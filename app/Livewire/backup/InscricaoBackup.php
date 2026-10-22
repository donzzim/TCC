<?php

namespace App\Livewire;


use App\Filament\Resources\Administrativo\DemandaResource;
use App\Filament\Resources\Escola\CursosResource\Pages;
use App\Filament\Resources\CursosResource\RelationManagers;
use App\Filament\Resources\TagResource;
use App\Filament\Resources\UserResource;
use App\Models\Administrativo\Agenda;
use App\Models\Escola\CursoComentarios;
use App\Models\Administrativo\Demanda;
use App\Models\Cadastro\Tabelas;
use App\Models\Escola\AreaTematica;
use App\Models\Escola\TipoLocal;
use App\Models\Escola\TipoRecursos;
use Closure;
use Exception;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;


use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Livewire\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TextInput\Mask;
use App\Models\Escola\CursoAlunos;
use App\Models\Escola\CursoFrequencias;
use App\Models\Escola\Cursos;
use App\Models\Escola\TipoGenero;
use App\Models\Escola\TipoRaca;
use App\Models\Escola\TipoSexo;
use App\Models\Recadastro;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Validate;

class Inscricao extends Component implements HasForms
{
    use InteractsWithForms;

    public array $data = [];
    public $uuid;
    public $curso;


    public function mount($uuid): void
    {
        $this->uuid = $uuid;
        $datahoje = Carbon::now();
        $row = Cursos::where('uuid', $uuid)->first();
        $atv = $row->atividades->last();
        $this->curso = $row;
    }


    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(
                [
                    Toggle::make('validado')
                        ->live()
                        ->default(false)
                        ->hidden(true)
                        ->columnSpanFull(),

                    TextInput::make('cpf')
                        ->label('CPF')
                        ->live()
                        ->required()
                        ->mask('999.999.999-99', [
                            'alias' => 'cpf',
                            'removeMaskOnSubmit' => true,
                        ])
                        ->afterStateUpdated(function ($state, Set $set) {
                            dd($state);
                            $this->pesquisarUsuario($state, $set);
                        })
                        ->maxLength(14)
                        ->columnSpanFull(),
                    Section::make('cadastro')
                        ->schema([
                            TextInput::make('nome')
                                ->label('Nome Completo')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),

                            TextInput::make('email')
                                ->label('E-mail')
                                ->email()
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),


                            TextInput::make('telefone')
                                ->label('Telefone')
                                ->required()
                                ->maxLength(15)
                                ->columnSpanFull(),

                            Select::make('sexo')
                                ->label('Sexo')
                                ->required()
                                ->options(function () {
                                    return TipoSexo::all()->pluck('descricao', 'id');
                                })
                                ->columnSpanFull(),

                            Select::make('genero')
                                ->label('Gênero')
                                ->required()
                                ->options(function () {
                                    return TipoGenero::all()->pluck('descricao', 'id');
                                })
                                ->columnSpanFull(),

                            Select::make('raca')
                                ->label('Cor/Raça')
                                ->required()
                                ->options(function () {
                                    return TipoRaca::all()->pluck('descricao', 'id');
                                })
                                ->columnSpanFull(),

                        ])
                        ->live()
                        ->hidden(function (Get $get) {
                            return $get('validado') == false;
                        })
                        ->columns(1),



                    // Nome Social, Nome Completo, E-mail, Telefone, sexo, Genero, Cor/Raça, PCD, ocupação, setor
                ]

            )
            ->columns(4);
    }




    public function render()
    {
        return view('livewire.inscricao');
    }

    public function submit()
    {
        // dd($this->data);
    }

    public function pesquisarUsuario($cpf, Set $set)
    {
        logger()->info('Inscricao::pesquisarUsuario');

        $user = User::where('cpf', $cpf)->get()->first();
        // dd($user);
        if ($user) {
            $inscricao = CursoAlunos::where('aluno_id', $user->id)
                ->where('curso_id', $this->curso->id)
                ->first();

            if ($inscricao) {

                Notification::make()
                    ->title('A sua inscrição já foi realizada para este curso!')
                    ->danger()
                    ->persistent()
                    ->send();
                return;
            }
            logger()->info('Usuario encontrado: ' . $user->name);
            // dd($this->cadastro);
            Notification::make()
                ->title('Seus dados foram encontrados em nossa base de dados!')
                ->success()
                ->persistent()
                ->send();

            $set('nome', $user->name);
            $set('email', $user->email);
            $set('telefone', $user->telefone);
            $set('sexo', $user->perfil->sexo ?? '');
            $set('genero', $user->perfil->genero ?? '');
            $set('raca', $user->perfil->raca ?? '');
            //pcd e necessidades_pcd
            return;
        } else {
            $recadastro = Recadastro::where('cpf', $cpf)->first();

            if (!$recadastro) {
                logger()->info('Usuario não encontrado no recadastro');

                Notification::make()
                    ->title('Usuário não encontrado insira seus dados!')
                    ->warning()
                    ->persistent()
                    ->send();
                return;
            } else {
                logger()->info('Usuario encontrado no recadastro: ' . $recadastro->nm_pessoa);
                Notification::make()
                    ->title('Seus dados foram encontrados em nossa base de dados!')
                    ->success()
                    ->persistent()
                    ->send();


                $set('cpf', $cpf);
                $set('nome', $recadastro->nome);
                $set('email', $recadastro->email);
                $set('telefone', "27{$recadastro->telefone_celular}");
                // $set('pcd', $recadastro->deficiente ?? []);
                return;
            }
        }
    }
}
