<?php

namespace App\Services;

use App\Http\Controllers\PrincipalController;
use App\Mail\AprovacaoMail;
use App\Mail\CertificadoMail;
use App\Mail\ComunicacaoMail;
use App\Mail\InscricaoMail;
use App\Models\Administrativo\Agenda;
use App\Models\Administrativo\Demanda;
use App\Models\Administrativo\RegistroFuncional;
use App\Models\Administrativo\Tarefas;
use App\Models\Escola\CursoAlunos;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\CursoFrequencias;
use App\Models\Escola\Cursos;
use App\Models\Escola\FormAvaliacao;
use App\Models\Recadastro;
use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class UserService
{
    public function pesquisarUsuario($cpf)
    {
        if (!$cpf) {
            return null;
        }
        $cpf = preg_replace("/[^0-9]/", "", $cpf);

        $user = User::where('cpf', $cpf)->get()->first();

        if (!$user) {

            $recadastro = Recadastro::where('cpf', $cpf)->whereNotNull('email')->first();

            if (!$recadastro) {
                logger()->info('Usuario não encontrado no recadastro');

                Notification::make()
                    ->title('Usuário não encontrado realize o cadastro para o registro da frequência!')
                    ->warning()
                    ->send();
                return $recadastro;
            } else {

                Notification::make()
                    ->title('Seus dados foram encontrados em nossa base de dados, complete o cadastro para o registro da frequência!')
                    ->success()
                    ->send();

                return $recadastro;
            }
        }
        return $user;
    }
}
