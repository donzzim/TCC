<?php

namespace App\Monitoramento\Console;

use App\Monitoramento\Agregacao\AgregadorDeRequisicoes;
use App\Monitoramento\Armazenamento\RepositorioDeMetricas;
use App\Monitoramento\Coleta\BufferDeRequisicoes;
use App\Monitoramento\Coleta\ColetorDeSnapshot;
use Illuminate\Console\Command;
use Throwable;

class ColetarMetricas extends Command
{
    public const TAREFA = 'monitoramento:coletar';

    protected $signature = 'monitoramento:coletar';

    protected $description = 'Agrega as requisições do buffer e grava as métricas do monitoramento';

    public function handle(RepositorioDeMetricas $repositorio, BufferDeRequisicoes $buffer): int
    {
        if (! config('monitoramento.ativo')) {
            return self::SUCCESS;
        }

        $trava = $this->travar($buffer);

        if ($trava === null) {
            return self::SUCCESS;
        }

        try {
            if (! $repositorio->instalado()) {
                $this->call('monitoramento:instalar');
            }

            $agora = now()->getTimestamp();
            $arquivos = array_unique([...$buffer->pendentesAntigos($agora, 600), ...$buffer->reservarProntos($agora)]);

            foreach ($arquivos as $arquivo) {
                $this->processar($arquivo, $repositorio, $buffer);
            }

            $repositorio->gravarSnapshot(ColetorDeSnapshot::capturar($agora));
            $repositorio->consolidarHora(intdiv($agora, 3600) * 3600 - 3600);
            $repositorio->limpar($agora);
            $repositorio->registrarBatimento(self::TAREFA, $agora, true);
        } catch (Throwable $e) {
            rescue(fn () => $repositorio->registrarBatimento(self::TAREFA, now()->getTimestamp(), false, mb_strimwidth($e->getMessage(), 0, 300)), null, false);
            report($e);

            return self::FAILURE;
        } finally {
            flock($trava, LOCK_UN);
            fclose($trava);
        }

        return self::SUCCESS;
    }

    private function processar(string $arquivo, RepositorioDeMetricas $repositorio, BufferDeRequisicoes $buffer): void
    {
        $resultado = AgregadorDeRequisicoes::agregar($buffer->ler($arquivo));

        $repositorio->gravarAgregados($resultado['agregados']);
        $repositorio->gravarDetalhadas($resultado['detalhados']);
        $buffer->descartar($arquivo);
    }

    private function travar(BufferDeRequisicoes $buffer)
    {
        $pasta = dirname($buffer->diretorio());

        if (! is_dir($pasta)) {
            @mkdir($pasta, 0775, true);
        }

        $trava = @fopen($pasta.'/coletar.lock', 'c');

        if ($trava === false) {
            return null;
        }

        if (! flock($trava, LOCK_EX | LOCK_NB)) {
            fclose($trava);

            return null;
        }

        return $trava;
    }
}
