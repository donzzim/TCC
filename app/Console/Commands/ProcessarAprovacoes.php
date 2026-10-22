<?php

namespace App\Console\Commands;

use App\Models\Escola\Cursos;
use App\Models\User;
use Filament\Notifications\Notification;
use App\Jobs\ProcessarCursosJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessarAprovacoes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:processar-aprovacoes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $eu = User::find(1); // Usuário administrador para notificações
        $cursos = Cursos::where('ativo', true)->where('auto', true)->with(['atividades', 'alunos'])->get();

        $cursos_futuros = $cursos->where('inicio', '>', now())->get();

        foreach ($cursos_futuros as $curso) {
            Log::info("Curso {$curso->titulo} programando para iniciar o curso");
        }



        $cursos_completos = $cursos->where('termino', '<=', now())->get();

        foreach ($cursos_completos as $curso) {

            Log::info("Enviando curso {$curso->titulo} para processamento de aprovações.");
            Log::info("Iniciando processamento de aprovações para o curso: {$curso->titulo}");

            ProcessarCursosJob::dispatch($curso);
        }

        Log::info(count($cursos) . ' cursos enviados para processamento de aprovacoes.');
        Log::info("//////////////////////////////////////////////////////////////////////////////////////////////////////////////");
    }
}
