<?php

namespace App\Services\Pedagogico;



use App\Models\Escola\CursoAlunos;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\CursoFrequencias;
use App\Models\Escola\Cursos;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AprovacaoService
{
    public static function aprovarAlunos($curso, $users = [], $observacao = null): int
    {
        if (count($users) <= 0) {
            $usersId = $curso->alunos()
                ->where('situacaoinscricao_id', config('settings.inscricao.inscrito'))
                ->where('aprovado', false)
                ->get()->pluck('aluno_id');
            $users = User::whereIn('id', $usersId)->get();
        } else {
            $users = collect($users);
        }
        // dd($users);

        $aprovados = 0;
        if ($curso->temAtividades()) {
            foreach ($curso->atividades as $atividade) {
                $aprovados += AprovacaoService::aprovarAlunos($atividade, $users);
            }
            foreach ($users as $user) {
                $cargaHorariaPresente = CursoAtividades::whereHas('alunos', function ($q) use ($user) {
                    $q->where('aluno_id', $user->id)
                        ->where('aprovado', true);
                })
                    ->where('curso_id', $curso->id)
                    ->where('certificavel', true)
                    ->sum('carga_horaria');
                $cargaHorariaMinima = $curso->carga_horaria * ($curso->aprovacao / 100);
                if ($cargaHorariaPresente >= $cargaHorariaMinima) {

                    $aluno = CursoAlunos::where('aluno_id', $user->id)->where('curso_id', $curso->id)->first();
                    $aluno->aprovar();

                    $aprovados++;
                    if ($observacao) {
                        $aluno->update(['observacao' => $observacao]);
                    }
                } else {
                    Log::info("Aluno {$user->name} não atingiu a frequência mínima na atividade {$curso->id}");
                    continue;
                }
            }
        } else {

            foreach ($users as $user) {
                try {
                    $frequenciaspresente = $user->frequencias()
                        ->where('curso_id', $curso->id)
                        ->where('situacaofrequencia_id', config('settings.frequencia.presente'))
                        ->count();

                    $totalfrequenciasatvd = $curso->frequencias()->where('aluno_id', $user->id)->count();

                    $frequencia = ($totalfrequenciasatvd > 0) ? ($frequenciaspresente / $totalfrequenciasatvd) * 100 : 0;
                    Log::info("Aluno {$user->name} total de frequencias na atividade {$curso->titulo}: {$totalfrequenciasatvd}, presente: {$frequenciaspresente}, frequência: {$frequencia}%");

                    if ($curso->aprovacao > 0 && $frequencia < $curso->aprovacao) {
                        Log::info("Aluno {$user->name} não atingiu a frequência mínima na atividade {$curso->id}");
                        continue;
                    }

                    //APROVACAO DO ALUNO
                    $aluno = CursoAlunos::where('aluno_id', $user->id)->where('curso_id', $curso->id)->first();
                    $aluno->aprovar();


                    $aprovados++;
                    Notification::make()
                        ->title('Aprovação')
                        ->body("Você foi aprovado no curso: {$curso->titulo}")
                        ->success()
                        ->sendToDatabase($user);
                    if ($observacao) {
                        $aluno->update(['observacao' => $observacao]);
                    }
                } catch (Exception $e) {
                    Log::error("Erro {$e->getMessage()} linha: {$e->getLine()}ao aprovar aluno {$user->name} no curso {$curso->titulo}.");
                    return -1;
                }
            }
        }


        return $aprovados;
    }
}
