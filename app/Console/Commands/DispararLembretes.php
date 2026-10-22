<?php

namespace App\Console\Commands;

use App\Models\Escola\Cursos;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DispararLembretes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:disparar-lembretes';

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

        $cursos = Cursos::where('ativo', true)
            ->whereBetween('inicio', [now(), now()->addDays(4)])
            ->with('atividades')
            ->get();
        $processados = 0;

        Log::info("Iniciando processo de disparo de lembretes para cursos próximos do início...");
        Log::info("Total de cursos encontrados para processamento: " . $cursos->count());

        foreach ($cursos as $curso) {
            try {

                $diffInDays = (int) now()->diffInDays($curso->inicio, false);

                Log::info("Processando curso: {$curso->titulo} - Início em: {$curso->inicio} - Diferença em dias: {$diffInDays}");


                if ($diffInDays === 3 || $diffInDays === 1) {
                    Log::info("Enviando curso {$curso->titulo} para processamento de lembretes.");
                    Log::info("Iniciando processamento de lembretes para o curso: {$curso->titulo}");
                    EmailService::dispararLembrete($curso, $diffInDays);
                    $processados++;
                }
            } catch (\Exception $e) {
                Log::error("Erro ao enviar lembrete para o curso {$curso->titulo}: " . $e->getMessage());
                continue;
            }
        }
        Log::info($processados . ' cursos enviados...');

        Log::info("//////////////////////////////////////////////////////////////////////////////////////////////////////////////");
    }
}
