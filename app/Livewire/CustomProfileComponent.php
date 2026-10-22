<?php

namespace App\Livewire;

use App\Models\Cadastro\Tabelas;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Jeffgreco13\FilamentBreezy\Livewire\MyProfileComponent;

class CustomProfileComponent extends MyProfileComponent
{
    protected string $view = 'livewire.custom-profile-component';
    public array $data;
    public $user;
    public $userClass;
    public static $sort = 11;
    public function mount()
    {
        $this->user = Filament::getCurrentPanel()->auth()->user();
        $this->userClass = get_class($this->user);

        $this->form->fill([
            'telefone' => $this->user->telefone,
            'cpf' => $this->user->cpf,
            'pcd' => $this->user->perfil->pcd ?? false,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados Pessoais')
                    ->columns(3)
                    ->schema([
                        Fieldset::make('Telefone e CPF')
                            ->schema([
                                Forms\Components\TextInput::make('telefone')
                                    ->mask('(99)99999-9999')
                                    ->required(),

                                Forms\Components\TextInput::make('cpf')
                                    ->mask('999.999.999-99')
                                    ->required(),
                            ]),

                        Fieldset::make('Deficiência')
                            ->schema([
                                Forms\Components\Toggle::make('pcd')
                                    ->label('Pessoa com Deficiência')
                                    ->inline(false)
                                    ->live()
                                    ->columnSpan(1),

                                Forms\Components\Select::make('pcd_necessidade')
                                    ->options(Tabelas::where('grupo', 'TIPO_DEFICIENCIA')->pluck('codigo', 'id'))
                                    ->searchable(['codigo'])
                                    ->multiple()
                                    ->label('Tipo de Deficiência')
                                    ->preload()
                                    ->columnSpan(2),
                            ])
                            ->columns(3),

                        Fieldset::make('Biotipo')
                            ->schema([
                                Forms\Components\Select::make('raca')
                                    ->options(Tabelas::where('grupo', 'RACA_COR')->pluck('descricao', 'id'))
                                    ->searchable(['descricao'])
                                    ->label('Raça/Cor')
                                    ->preload()
                                    ->columnSpan(1),

                                Forms\Components\Select::make('sexo')
                                    ->options(Tabelas::where('grupo', 'SEXO')->pluck('descricao', 'id'))
                                    ->searchable(['descricao'])
                                    ->label('Sexo')
                                    ->preload()
                                    ->columnSpan(1),

                                Forms\Components\Select::make('genero')
                                    ->options(Tabelas::where('grupo', 'IDENTIDADE_GENERO')->pluck('descricao', 'id'))
                                    ->searchable(['descricao'])
                                    ->label('Gênero')
                                    ->preload()
                                    ->columnSpan(1),
                            ])
                            ->columns(3),

                        Fieldset::make('Profissional')
                            ->schema([
                                Forms\Components\Toggle::make('chefia')
                                    ->inline(false)
                                    ->label('Chefia'),

                                Forms\Components\Select::make('tipopessoa_id')
                                    ->options(Tabelas::where('grupo', 'TIPO_PESSOA')->pluck('descricao', 'id'))
                                    ->searchable(['descricao'])
                                    ->label('Tipo de Pessoa')
                                    ->preload()
                                    ->columnSpan(2),
                            ])
                            ->columns(3),
                    ]),
            ])
            ->statePath('data');
    }

    // only capture the custome component field
    public function submit(): void
    {
        $data = collect($this->form->getState())->all();
        $user = ['telefone' => $data['telefone'] ?? null, 'cpf' => $data['cpf'] ?? null];
        $this->user->update($user);

        $perfil = [
            'pcd' => $data['pcd'],
            'pcd_necessidade' => $data['pcd_necessidade'],
            'raca' => $data['raca'],
            'sexo' => $data['sexo'],
            'genero' => $data['genero'],
            'chefia' => $data['chefia'],
            'tipopessoa_id' => $data['tipopessoa_id'],
        ];
        $this->user->perfil()->update($perfil);

        Notification::make()
            ->success()
            ->title('Dados atualizados corretamente!')
            ->send();
    }
}
