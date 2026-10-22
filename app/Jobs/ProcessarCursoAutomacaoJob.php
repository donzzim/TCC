<?php

namespace App\Jobs;

use App\Models\Escola\Cursos;
use App\Services\Auto\AutoNotificationService;
use App\Services\Auto\CursoAnalyzerService;
use App\Services\Auto\CursoClassifierService;
use App\Services\Auto\Support\StepExecutionFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessarCursoAutomacaoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public int $cursoId,
    ) {}

    public function handle(
        CursoAnalyzerService $analyzer,
        CursoClassifierService $classifier,
        AutoNotificationService $notificationService,
        StepExecutionFactory $executionFactory,
    ): void {
        $curso = Cursos::query()
            ->where('ativo', true)
            ->where('auto', true)
            ->with(['atividades', 'alunos'])
            ->find($this->cursoId);

        if (! $curso) {
            Log::info('Curso automatico nao encontrado ou nao elegivel para analise.', [
                'curso_id' => $this->cursoId,
            ]);

            return;
        }

        $previousAutoStatus = is_array($curso->auto_status) ? $curso->auto_status : null;
        $analysis = $analyzer->analyze($curso);
        $autoStatus = $classifier->classify($curso, $analysis);

        if (array_key_exists('released_breakpoints', $previousAutoStatus ?? [])) {
            $autoStatus['released_breakpoints'] = $previousAutoStatus['released_breakpoints'];
        }

        Cursos::withoutEvents(function () use ($curso, $analysis, $autoStatus): void {
            $curso->forceFill([
                'auto_steps' => $analysis['steps'] ?? [],
                'auto_status' => $autoStatus,
            ])->save();
        });

        $notificationService->notifyAfterClassification(
            curso: $curso,
            analysis: $analysis,
            autoStatus: $autoStatus,
            previousAutoStatus: $previousAutoStatus,
        );

        $notificationService->notifyBreakpointsAfterClassification(
            curso: $curso,
            analysis: $analysis,
            autoStatus: $autoStatus,
            previousAutoStatus: $previousAutoStatus,
        );

        $this->dispatchConfirmedSteps($curso, $autoStatus, $executionFactory);

        Log::info('Curso automatico analisado e classificado.', [
            'curso_id' => $curso->id,
            'status' => $autoStatus['status'] ?? null,
            'confirmed_dispatchable_steps' => $autoStatus['confirmed_dispatchable_steps'] ?? [],
        ]);
    }

    private function dispatchConfirmedSteps(
        Cursos $curso,
        array $autoStatus,
        StepExecutionFactory $executionFactory,
    ): void {
        $dispatchableSteps = collect($autoStatus['confirmed_dispatchable_steps'] ?? [])
            ->filter(fn(mixed $stepKey): bool => is_string($stepKey) && filled($stepKey))
            ->values();

        foreach ($dispatchableSteps as $stepKey) {
            if (! $executionFactory->has($stepKey)) {
                Log::info('Etapa automatica pronta, mas sem executor registrado.', [
                    'curso_id' => $curso->id,
                    'step' => $stepKey,
                ]);

                continue;
            }

            ExecutarEtapaAutomacaoJob::dispatch($curso->id, $stepKey);

            return;
        }
    }
}
