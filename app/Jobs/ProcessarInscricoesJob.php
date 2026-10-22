<?php

namespace App\Jobs;

use App\Http\Controllers\PublicFormController;
use App\Models\Escola\CursoAlunos;
use App\Models\Escola\Cursos;
use App\Models\Escola\TipoPublico;
use App\Models\Settings;
use App\Models\User;
use App\Services\AprovacaoService;
use Illuminate\Support\Facades\Cache;
use App\Services\Pedagogico\CertificadoService;
use App\Services\Pedagogico\InscricaoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessarInscricoesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Dados que o job vai processar
     */
    public Cursos $curso;

    /**
     * Cria uma nova instância do job
     */
    public function __construct(Cursos $curso)
    {
        $this->curso = $curso;
    }

    /**
     * Executa o job
     */
    public function handle(): void
    {
        try {

            $config = $this->curso->autoconfig;
            $config_inscricao = $config['inscricao'];
            $users = $this->curso->alunosUser()->get();

            if ($config_inscricao['inscrever_publico']) {


                $publicos_alvos = $this->curso->publico()->pluck('id')->toArray();

                $publicos_indiretos = TipoPublico::whereIn('codigo', $publicos_alvos)->pluck('id')->toArray();

                $publicos_alvos_ids = array_merge($publicos_alvos, $publicos_indiretos);

                if (in_array(2101, $publicos_alvos_ids)) { // 2101 é o código do público "Todos"
                    $users_alvo = $users;
                } else {
                    $users_alvo = $users->filter(function ($user) use ($publicos_alvos_ids) {
                        if ($user->perfil->tipopessoa_id) {
                            return in_array($user->perfil->tipopessoa_id, $publicos_alvos_ids) || $user->perfil->tipopessoa_id == 2104; // 2104 é o código do público "magistrados", podem participar de qualquer curso independente do público alvo, por isso a inclusão nessa condição.   
                        };
                    });
                }


                $atividades_id = [];

                if ($this->curso->temAtividades()) {
                    $atividades = $this->curso->atividades()->get();
                    $atividades_id[] = $this->curso->id;
                    foreach ($atividades as $atividade) {
                        $atividades_id[] = $atividade->id;
                    }
                    $preInscricoes = CursoAlunos::whereIn('aluno_id', $users_alvo->pluck('id')->toArray())
                        ->whereIn('curso_id', $atividade->id)
                        ->where('situacaoinscricao_id', config('settings.inscricao.preinscricao'))->get();

                    InscricaoService::inscreverPreinscricoes($preInscricoes);
                } else {
                    $vagas = $this->curso->vagas();
                }
            }
        } catch (\Exception $e) {
            Log::error("Erro ao processar o curso {$this->curso->titulo}: " . $e->getMessage() . "Arquivo: " . $e->getFile() . " Linha: " . $e->getLine());
            return;
        }
    }
}
