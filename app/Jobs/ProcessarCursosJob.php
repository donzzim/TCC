<?php

namespace App\Jobs;

use App\Models\Escola\Cursos;
use App\Models\Settings;
use App\Services\AprovacaoService;
use Illuminate\Support\Facades\Cache;
use App\Services\Pedagogico\CertificadoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessarCursosJob implements ShouldQueue
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



            Log::info('Iniciando Job do curso');
            if ($this->curso->aprovacoesPendentes()) {
                Log::info('Processando job...', [
                    'curso' => $this->curso,
                ]);

                $alunos = $this->curso->alunos()->where('situacaoinscricao_id', config('settings.inscricao.inscrito'))->where('aprovado', false)->with('aluno')->get();


                $aprovados = AprovacaoService::aprovarAlunos($this->curso, $alunos, 'Aluno aprovado automaticamente pelo sistema.');
                Log::info("Total de alunos aprovados no curso {$this->curso->titulo}: {$aprovados}");

                Log::info('Job finalizado com sucesso');
            }

            $data = [
                'observacao' => 'Certificado gerado automaticamente pelo sistema.',
                'modelo_id' => 1,
                'assinado_por' => config('settings.assinaturas.diretor'),
                'disponivel' => false,
                'totalatvd' => false,
                'auto' => true,
            ];

            if ($this->curso->temAtividades()) {

                foreach ($this->curso->atividades as $atividade) {
                    Log::info("Processando certificação na atividade {$atividade->titulo} do curso {$this->curso->titulo} DATA: " . json_encode($data));
                    CertificadoService::gerarCertificados($data, $atividade);
                    Log::info("Certificação processada na atividade {$atividade->titulo} do curso {$this->curso->titulo}");
                }
            } else {
                Log::info("Processando certificação no curso {$this->curso->titulo}");
                CertificadoService::gerarCertificados($data, $this->curso);
                log::info("Certificação processada no curso {$this->curso->titulo}");
            }

            $this->curso->update([
                'auto' => false,
                'atualizado_por' => 1,
            ]);

            Log::info("Processamento automático do curso {$this->curso->titulo} finalizado.");
        } catch (\Exception $e) {
            Log::error("Erro ao processar o curso {$this->curso->titulo}: " . $e->getMessage() . "Arquivo: " . $e->getFile() . " Linha: " . $e->getLine());
            return;
        }
    }
}
