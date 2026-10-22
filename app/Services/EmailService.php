<?php

namespace App\Services;

use App\Http\Controllers\PrincipalController;
use App\Jobs\EmailInscricao;
use App\Mail\AprovacaoMail;
use App\Mail\CertificadoMail;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use App\Mail\ComunicacaoMail;
use App\Mail\InscricaoMail;
use App\Mail\InstrutorMail;
use App\Mail\LembreteMail;
use App\Models\Administrativo\Agenda;
use App\Models\Escola\CursoAlunos;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\CursoFrequencias;
use App\Models\Escola\Cursos;
use App\Models\Escola\FormAvaliacao;
use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailService
{

    public static function validarEmail(?string $email): bool
    {
        $email = trim((string) $email);
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function montarConteudoEmailInscricao($aluno, $user, $curso)
    {

        $local = null;

        if (!$curso->curso_id) {
            $atividades = $curso->atividades()->with('agendas')->get();
        }

        if ($aluno instanceof CursoAlunos) {
            $cancenlarUrl = route('cancelar.inscricao', ['uuid' => $aluno->uuid]);
        }

        if ($curso->tipoLocal()) {
            $local = $curso->tipoLocal;
            if ($local) {
                $local = $local->descricao;
            }
        }

        $conteudo = [
            'titulo' => $curso->titulo ?? null,
            'inicio' => isset($curso->inicio) ? Carbon::parse($curso->inicio)->format('d/m/Y H:i') : null,
            'termino' => isset($curso->termino) ? Carbon::parse($curso->termino)->format('d/m/Y H:i') : null,
            'atividades' => $atividades ?? null,
            'aluno' => $user->name ?? 'Aluno',
            'link_cancelamento' => $cancenlarUrl ?? null,
            'local' => $local ?? null,
            'link_reuniao' => $curso->link ?? null,
            'senha_reuniao' => $curso->webnar_password ?? null,
        ];

        return $conteudo;
    }




    public static function dispararEmailInscricao(array $alunos)
    {
        $alunos = EloquentCollection::make($alunos);

        $jobs = [];

        foreach ($alunos as $aluno) {
            $jobs[] = new EmailInscricao($aluno->id);
        }

        if (empty($jobs)) {
            return response()->json([
                'status' => 'empty',
                'message' => 'Nenhum aluno encontrado para envio.',
            ]);
        }

        $batch = Bus::batch($jobs)
            ->name('Disparo de e-mails de inscrição')
            ->allowFailures()
            ->onQueue('default')
            ->then(function ($batch) {
                Log::info('Batch de e-mails de inscrição finalizado com sucesso.', [
                    'batch_id' => $batch->id,
                ]);
            })
            ->catch(function ($batch, Throwable $e) {
                Log::error('Batch de e-mails de inscrição teve falha.', [
                    'batch_id' => $batch->id,
                    'error' => $e->getMessage(),
                ]);
            })
            ->finally(function ($batch) {
                Log::info('Batch de e-mails de inscrição encerrado.', [
                    'batch_id' => $batch->id,
                    'total_jobs' => $batch->totalJobs,
                    'pending_jobs' => $batch->pendingJobs,
                    'failed_jobs' => $batch->failedJobs,
                ]);
            })
            ->dispatch();

        return response()->json([
            'status' => 'ok',
            'batch_id' => $batch->id,
        ]);
    }




    public static function dispararEmailInstrutor($instrutores)
    {
        $instrutores = EloquentCollection::make($instrutores);
        $instrutores->load(['instrutor', 'curso', 'curso.atividades', 'curso.agendas', 'curso.atividades.agendas']);

        foreach ($instrutores as $instrutor) {

            $user = $instrutor->instrutor;
            $curso = $instrutor->curso;
            if ($user && $user->email) {
                if (!self::validarEmail($user->email)) {
                    Log::warning("Email inválido para o usuário ID {$user->id} : {$user->email}");
                    return response()->json(['status' => 'ok']);
                }
                $conteudo = self::montarConteudoEmailInscricao($instrutor, $user, $curso);
                // dd($conteudo);

                Mail::to($user->email)->queue(new InstrutorMail($conteudo));
            }
        }
        return response()->json(['status' => 'ok']);
    }


    public static function dispararLembrete($curso, $diffInDays)
    {
        Log::info("Iniciando processamento de lembretes para o curso: {$curso->titulo}");

        $alunos = $curso->alunos()->where('situacaoinscricao_id', 2802)->with('aluno')->get();
        Log::info("[LEMBRETES] Total de alunos inscritos para o curso {$curso->titulo}: " . $alunos->count());

        foreach ($alunos as $aluno) {
            Log::info("Processando email de lembrete para aluno ID {$aluno->aluno_id} para o curso {$curso->titulo}");
            $user = $aluno->aluno;
            if ($user && $user->email) {
                if (!self::validarEmail($user->email)) {
                    Log::warning("Email inválido para o usuário ID {$user->id} : {$user->email}");
                    continue;
                }
                Log::info("Preparando e-mail de lembrete para o aluno: {$user->name} ({$user->email}) - Curso: {$curso->titulo} - Diferença em dias: {$diffInDays}");
                //reutilizar o método de montagem de conteúdo do email de inscrição, pois o conteúdo é semelhante
                $conteudo = self::montarConteudoEmailInscricao($aluno, $user, $curso);
                $conteudo['diffDays'] = $diffInDays;


                Log::info("Conteúdo do e-mail de lembrete para o aluno {$user->name} ({$user->email}):", $conteudo);

                Mail::to($user->email)->queue(new LembreteMail($conteudo));
            }
        }
    }


    public static function emailAprovacao($alunos)
    {
        foreach ($alunos as $aluno) {

            $conteudo['titulo'] = $aluno->curso->titulo ?? $aluno->atividade->titulo;
            $form = FormAvaliacao::where('ativo', true)->first();
            $formUrl = route('forms.show', ['slug' => $form->slug, 'uuid' => $aluno->uuid]);

            $user = User::find($aluno->aluno_id);

            if ($user && $user->email) {
                if (!self::validarEmail($user->email)) {
                    Log::warning("Email inválido para o usuário ID {$user->id} : {$user->email}");
                    continue;
                }
                Mail::to($user->email)->queue(new AprovacaoMail($conteudo, $formUrl));
            }
        }

        return response()->json(['status' => 'ok']);
    }

    public static function certificadoLiberado($alunos)
    {
        foreach ($alunos as $aluno) {

            $conteudo['titulo'] = $aluno->curso->titulo ?? $aluno->atividade->titulo;

            $user = User::find($aluno->aluno_id);

            if ($user && $user->email) {
                if (!self::validarEmail($user->email)) {
                    Log::warning("Email inválido para o usuário ID {$user->id} : {$user->email}");
                    continue;
                }
                $conteudo['aluno'] = $user->name;
                Mail::to($user->email)->queue(new CertificadoMail($conteudo));
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
