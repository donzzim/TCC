<?php

namespace App\Jobs;

use App\Models\Escola\Cursos;
use App\Services\Auto\AutoExecutionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExecutarEtapaAutomacaoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public int $cursoId,
        public string $stepKey,
    ) {}

    public function handle(AutoExecutionService $executionService): void
    {
        $curso = Cursos::query()
            ->where('ativo', true)
            ->where('auto', true)
            ->with(['atividades', 'alunos'])
            ->find($this->cursoId);

        if (! $curso) {
            Log::info('Curso automatico nao encontrado ou nao elegivel para execucao.', [
                'curso_id' => $this->cursoId,
                'step' => $this->stepKey,
            ]);

            return;
        }

        $result = $executionService->execute(
            curso: $curso,
            stepKey: $this->stepKey,
            context: [
                'auto_status' => is_array($curso->auto_status) ? $curso->auto_status : [],
                'triggered_by' => self::class,
            ],
        );

        if ($result['should_reanalyze'] ?? true) {
            ProcessarCursoAutomacaoJob::dispatch($this->cursoId);
        }
    }
}
