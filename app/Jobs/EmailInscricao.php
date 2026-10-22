<?php

namespace App\Jobs;

use App\Mail\InscricaoMail;
use App\Models\Escola\CursoAlunos;
use App\Models\Escola\Cursos;
use App\Services\EmailService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class EmailInscricao implements ShouldQueue
{
    use Batchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public int $cursoAlunoId,
    ) {}

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $aluno = CursoAlunos::query()
            ->with(['aluno', 'curso', 'atividade', 'curso.atividades', 'curso.agendas', 'atividade.agendas'])
            ->find($this->cursoAlunoId);

        if (! $aluno) {
            return;
        }

        $user = $aluno->aluno;

        if (! $user || ! $user->email) {
            return;
        }

        if (! EmailService::validarEmail($user->email)) {
            return;
        }

        $curso = $aluno->curso ?? $aluno->atividade;

        if (! $curso) {
            return;
        }

        $conteudo = EmailService::montarConteudoEmailInscricao($aluno, $user, $curso);

        Mail::to($user->email)->send(new InscricaoMail($conteudo));
    }
}
