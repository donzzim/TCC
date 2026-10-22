<?php

namespace App\Livewire;



use Filament\Notifications\Notification;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Livewire\Component;
use App\Models\Escola\CursoAlunos;
use App\Models\Escola\Cursos;
use App\Services\Pedagogico\InscricaoService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use OpenApi\Attributes\Get;

class CancelarInscricao extends Component implements HasForms
{
    use InteractsWithForms;

    public $uuid;
    public $curso = null;
    public $disponivel = false;
    public $aluno;



    public function mount($uuid): void
    {
        $this->aluno = CursoAlunos::where('uuid', $uuid)->where('situacaoinscricao_id', config('settings.inscricao.inscrito'))->first();
        if ($this->aluno) {
            $this->curso = Cursos::find($this->aluno->curso_id);
            $this->disponivel = true;
        }
    }



    public function form(Schema $schema): Schema
    {

        return $schema
            ->components(
                [
                    TextInput::make('cpf')
                        ->label('CPF')
                        ->live(onBlur: true)
                        ->required()
                        ->mask('999.999.999-99')
                        ->suffixAction(
                            Action::make('pesquisar')
                                ->icon('heroicon-o-magnifying-glass')
                                ->action(function (Get $get) {
                                    $this->aluno = CursoAlunos::whereHas('aluno', function ($query) use ($get) {
                                        $query->where('cpf', $get('cpf'));
                                    })->where('curso_id', $this->curso->id)
                                        ->where('situacaoinscricao_id', config('settings.inscricao.inscrito'))
                                        ->first();
                                })
                        )
                        // ->extraAttributes([
                        //     'wire:keydown.enter.prevent' => "mountFormComponentAction('cpf', 'pesquisar')",
                        // ])
                        ->maxLength(14)
                        ->columnSpanFull(),
                ]
            )
            ->columns(4);
    }
    public function submit()
    {
        InscricaoService::cancelarInscricao($this->curso, [$this->aluno->aluno]);
        Notification::make()
            ->title('Inscrição cancelada com sucesso!')
            ->success()
            ->send();
        return redirect();
    }




    public function render()
    {
        return view('livewire.cancelarInscricao');
    }
}
