<?php

namespace App\Services\Pedagogico;




use App\Services\EmailService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InscricaoService
{

    public static function preInscrever($evento, $users, $observacao = null)
    {

        $atividades = [];
        $pai = null;
        if ($evento->temAtividades()) {
            $atividades = $evento->atividades;
        }
        if ($evento->curso) {
            $pai = $evento->curso;
        }

        foreach ($users as $aluno) {
            $evento->alunos()->updateOrCreate(
                [
                    'aluno_id' => $aluno->id,
                    'curso_id' => $evento->id,
                ],
                [
                    'aluno_id' => $aluno->id,
                    'observacao' => $observacao,
                    'data_inscricao' => Carbon::now(),
                    'curso_id' => $evento->id,
                    'situacaoinscricao_id' => config('settings.inscricao.preinscricao'),
                    'atualizado_por' => Auth::user()->id ?? $aluno->id,
                ]
            );
            if (count($atividades)) {

                foreach ($atividades as $atividade) {
                    $atividade->alunos()->updateOrCreate(
                        [
                            'aluno_id' => $aluno->id,
                            'curso_id' => $atividade->id,
                        ],
                        [
                            'aluno_id' => $aluno->id,
                            'data_inscricao' => Carbon::now(),
                            'curso_id' => $atividade->id,
                            'situacaoinscricao_id' => config('settings.inscricao.preinscricao'),
                            'atualizado_por' => Auth::user()->id ?? $aluno->id,
                        ]
                    );
                }
            }
            if ($pai) { //verifico se tem pai
                $pai->alunos()->updateOrCreate(
                    [
                        'aluno_id' => $aluno->id,
                        'curso_id' => $pai->id,
                    ],
                    [
                        'aluno_id' => $aluno->id,
                        'observacao' => $observacao,
                        'data_inscricao' => Carbon::now(),
                        'curso_id' => $pai->id,
                        'situacaoinscricao_id' => config('settings.inscricao.preinscricao'),
                        'atualizado_por' => Auth::user()->id ?? $aluno->id,
                    ]
                );
            }
            Log::info("Aluno {$aluno->name} pré-inscrito no curso {$evento->titulo}");
            Notification::make()
                ->title('Pré-inscrição')
                ->body("Você foi pré-inscrito no curso: {$evento->titulo}")
                ->success()
                ->sendToDatabase($aluno);

            Notification::make()
                ->title('Pré-inscrição realizada com sucesso!')
                ->success()
                ->send();
        }
        return;
    }



    public static function inscrever($evento, $users, $notificar = true, $observacao = null, $frequencias = true)
    {
        $atividades = [];
        $pai = null;
        if ($evento->temAtividades()) {
            $atividades = $evento->atividades;
        }
        if ($evento->curso) {
            $pai = $evento->curso;
        }

        foreach ($users as $aluno) {
            $evento->alunos()->updateOrCreate(
                [
                    'aluno_id' => $aluno->id,
                    'curso_id' => $evento->id,
                ],
                [
                    'aluno_id' => $aluno->id,
                    'observacao' => $observacao,
                    'data_inscricao' => Carbon::now(),
                    'curso_id' => $evento->id,
                    'situacaoinscricao_id' => config('settings.inscricao.inscrito'),
                    'atualizado_por' => Auth::user()->id ?? $aluno->id,
                ]
            );
            if (count($atividades) > 0) {

                foreach ($atividades as $atividade) {
                    $atividade->alunos()->updateOrCreate(
                        [
                            'aluno_id' => $aluno->id,
                            'curso_id' => $atividade->id,
                        ],
                        [
                            'aluno_id' => $aluno->id,
                            'data_inscricao' => Carbon::now(),
                            'curso_id' => $atividade->id,
                            'situacaoinscricao_id' => config('settings.inscricao.inscrito'),
                            'atualizado_por' => Auth::user()->id ?? $aluno->id,
                        ]
                    );
                    if ($frequencias) {
                        FrequenciaService::gerarFrequencias($atividade, $aluno);
                    }
                }
            } elseif ($frequencias) {
                FrequenciaService::gerarFrequencias($evento, $aluno);
            }
            if ($pai) { //verifico se tem pai
                $pai->alunos()->updateOrCreate(
                    [
                        'aluno_id' => $aluno->id,
                        'curso_id' => $pai->id,
                    ],
                    [
                        'aluno_id' => $aluno->id,
                        'observacao' => $observacao,
                        'data_inscricao' => Carbon::now(),
                        'curso_id' => $pai->id,
                        'situacaoinscricao_id' => config('settings.inscricao.inscrito'),
                        'atualizado_por' => Auth::user()->id ?? $aluno->id,
                    ]
                );
            }
            $inscricao = $evento->alunos()->where('aluno_id', $aluno->id)->first();
            if ($notificar) {
                EmailService::dispararEmailInscricao([$inscricao]);
            }
            Log::info("Aluno {$aluno->name} Inscrito no curso {$evento->titulo}");
            Notification::make()
                ->title('Pré-inscrição')
                ->body("Você foi Inscrito no curso: {$evento->titulo}")
                ->success()
                ->sendToDatabase($aluno);

            Notification::make()
                ->title('Inscrição realizada com sucesso!')
                ->success()
                ->send();
        }
        return;
    }



    public static function cancelarInscricao($evento, $users)
    {
        $atividades = [];
        $pai = null;
        if ($evento->temAtividades()) {
            $atividades = $evento->atividades;
        }

        foreach ($users as $aluno) {
            $evento->alunos()->updateOrCreate(
                [
                    'aluno_id' => $aluno->id,
                    'curso_id' => $evento->id,
                ],
                [
                    'aluno_id' => $aluno->id,
                    'data_inscricao' => Carbon::now(),
                    'curso_id' => $evento->id,
                    'situacaoinscricao_id' => config('settings.inscricao.cancelada'),
                    'atualizado_por' => Auth::user()->id ?? $aluno->id,
                    'observacao' => 'Cancelado Pelo Usuario'
                ]
            );
            if (count($atividades) > 0) {

                foreach ($atividades as $atividade) {
                    $atividade->alunos()->updateOrCreate(
                        [
                            'aluno_id' => $aluno->id,
                            'curso_id' => $atividade->id,
                        ],
                        [
                            'aluno_id' => $aluno->id,
                            'data_inscricao' => Carbon::now(),
                            'curso_id' => $atividade->id,
                            'situacaoinscricao_id' => config('settings.inscricao.cancelada'),
                            'atualizado_por' => Auth::user()->id ?? $aluno->id,
                            'observacao' => 'Cancelado Pelo Usuario'
                        ]
                    );
                }
            }
            Log::info("Aluno {$aluno->name} Inscrito no curso {$evento->titulo}");
            Notification::make()
                ->title('Cancelamento de Inscrição')
                ->body("Sua inscrição foi cancelada no curso: {$evento->titulo}")
                ->success()
                ->sendToDatabase($aluno);
        }
        return;
    }



    public static function inscreverPreinscricoes($preInscricoes)
    {
        DB::beginTransaction();
        foreach ($preInscricoes as $inscricao) {
            try {
                $curso = $inscricao->curso ?? $inscricao->atividade;
                $user = $inscricao->aluno;
                Log::info("Convertendo pré-inscrição em inscrição para o aluno {$user->name} : ID {$user->id} no curso {$user->titulo} : ID {$user->id}");
                $inscricao->update(
                    [
                        'situacaoinscricao_id' => config('settings.inscricao.inscrito'),
                        'atualizado_por' => Auth::user()->id ?? $user->id,
                    ]
                );
                FrequenciaService::gerarFrequencias($curso, $user);
                if ($inscricao->curso) {
                    EmailService::dispararEmailInscricao([$inscricao]);
                }
            } catch (\Exception $e) {
                Log::error("Erro ao converter pré-inscrição em inscrição para o aluno {$user->name} : ID {$user->id} no curso {$user->titulo} : ID {$user->id} - Erro: {$e->getMessage()}");
                Notification::make()
                    ->title('Erro ao Inscrever pré-inscrição')
                    ->body("Ocorreu um erro ao tentar converter a pré-inscrição em inscrição para o curso {$curso->titulo}: {$e->getMessage()}")
                    ->danger()
                    ->send();
                DB::rollBack();
                return;
            }
        }
        DB::commit();
        return;
    }
}
