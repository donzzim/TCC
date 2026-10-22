<?php

namespace App\Console\Commands;

use App\Jobs\ProcessarCursoAutomacaoJob;
use App\Models\Escola\Cursos;
use Illuminate\Console\Command;

class ProcessarCursos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:processar-cursos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Despacha o pipeline automatico dos cursos ativos.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dispatched = 0;

        Cursos::query()
            ->where('ativo', true)
            ->where('auto', true)
            ->select('id')
            ->chunkById(100, function ($cursos) use (&$dispatched): void {
                foreach ($cursos as $curso) {
                    ProcessarCursoAutomacaoJob::dispatch($curso->id);
                    $dispatched++;

                    $this->info("Curso {$curso->id} enviado para o pipeline automatico.");
                }
            });

        $this->info("{$dispatched} curso(s) enviado(s) para o pipeline automatico.");

        return self::SUCCESS;
    }
}
