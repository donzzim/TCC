<?php

namespace App\Monitoramento\Armazenamento;

use App\Monitoramento\Agregacao\AgregadoMinuto;
use App\Monitoramento\Agregacao\Histograma;
use App\Monitoramento\Coleta\RegistroDeRequisicao;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

class RepositorioDeMetricasSql implements RepositorioDeMetricas
{
    private const JSON = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE;

    public function __construct(private readonly string $conexao = 'monitoramento')
    {
    }

    protected function db(): ConnectionInterface
    {
        return DB::connection($this->conexao);
    }

    public function instalado(): bool
    {
        return $this->db()->getSchemaBuilder()->hasTable('agregados_minuto');
    }

    public function gravarAgregados(array $agregados): void
    {
        $this->db()->transaction(function () use ($agregados): void {
            foreach ($agregados as $a) {
                $this->somarNaTabela('agregados_minuto', 'minuto', $a->minuto, $a);
            }
        });
    }

    public function gravarDetalhadas(array $registros): void
    {
        $linhas = array_map(fn (RegistroDeRequisicao $r): array => [
            'request_id' => $r->requestId,
            'instante' => $r->instante,
            'contexto' => $r->contexto,
            'rota' => $r->rota,
            'metodo' => $r->metodo,
            'status' => $r->status,
            'duracao_ms' => $r->duracaoMs,
            'sql_ms' => $r->sqlMs,
            'sql_qtd' => $r->sqlQtd,
            'memoria_mb' => $r->memoriaMb,
            'livewire' => json_encode($r->livewire, self::JSON),
            'detalhe' => $r->detalhe === null ? null : json_encode($r->detalhe, self::JSON),
        ], $registros);

        foreach (array_chunk($linhas, 100) as $lote) {
            $this->db()->table('requisicoes')->insertOrIgnore($lote);
        }
    }

    public function consolidarHora(int $hora): void
    {
        $minutos = $this->db()->table('agregados_minuto')
            ->where('minuto', '>=', $hora)
            ->where('minuto', '<', $hora + 3600)
            ->get();

        $grupos = [];

        foreach ($minutos as $m) {
            $chave = $m->contexto."\0".$m->rota;
            $grupos[$chave] = isset($grupos[$chave])
                ? $this->somarLinhas($grupos[$chave], (array) $m)
                : [...(array) $m, 'faixas' => json_decode($m->faixas, true) ?: Histograma::vazio()];
        }

        $this->db()->transaction(function () use ($hora, $grupos): void {
            $this->db()->table('agregados_hora')->where('hora', $hora)->delete();

            foreach ($grupos as $g) {
                unset($g['id'], $g['minuto']);
                $this->db()->table('agregados_hora')->insert([...$g, 'hora' => $hora, 'faixas' => json_encode($g['faixas'])]);
            }
        });
    }

    public function limpar(int $agora): void
    {
        $limiteBruto = $agora - (int) config('monitoramento.retencao_bruta_horas', 48) * 3600;

        $this->db()->table('agregados_minuto')->where('minuto', '<', $limiteBruto)->delete();
        $this->db()->table('requisicoes')->where('instante', '<', $limiteBruto)->delete();
        $this->db()->table('agregados_hora')
            ->where('hora', '<', $agora - (int) config('monitoramento.retencao_hora_dias', 30) * 86400)
            ->delete();
        $this->db()->table('snapshots')
            ->where('instante', '<', $agora - (int) config('monitoramento.retencao_snapshots_dias', 14) * 86400)
            ->delete();
    }

    public function registrarBatimento(string $tarefa, int $quando, bool $sucesso, ?string $mensagem = null): void
    {
        $this->db()->table('batimentos')->updateOrInsert(
            ['tarefa' => $tarefa],
            ['ultima_execucao' => $quando, 'sucesso' => $sucesso, 'mensagem' => $mensagem],
        );
    }

    private function somarNaTabela(string $tabela, string $colunaTempo, int $tempo, AgregadoMinuto $a): void
    {
        $existente = $this->db()->table($tabela)
            ->where($colunaTempo, $tempo)
            ->where('contexto', $a->contexto)
            ->where('rota', $a->rota)
            ->first();

        $novo = [
            'qtd' => $a->qtd, 'erros_4xx' => $a->erros4xx, 'erros_5xx' => $a->erros5xx,
            'soma_ms' => $a->somaMs, 'max_ms' => $a->maxMs, 'soma_sql_ms' => $a->somaSqlMs,
            'soma_sql_qtd' => $a->somaSqlQtd, 'max_memoria_mb' => $a->maxMemoriaMb,
            'n_mais_um' => $a->nMaisUm, 'faixas' => $a->faixas,
        ];

        if ($existente === null) {
            $this->db()->table($tabela)->insert([
                ...$novo,
                $colunaTempo => $tempo,
                'contexto' => $a->contexto,
                'rota' => $a->rota,
                'faixas' => json_encode($a->faixas),
            ]);

            return;
        }

        $somado = $this->somarLinhas([...(array) $existente, 'faixas' => json_decode($existente->faixas, true) ?: Histograma::vazio()], $novo);

        $this->db()->table($tabela)->where('id', $existente->id)->update([
            ...array_intersect_key($somado, $novo),
            'faixas' => json_encode($somado['faixas']),
        ]);
    }

    private function somarLinhas(array $base, array $mais): array
    {
        foreach (['qtd', 'erros_4xx', 'erros_5xx', 'soma_sql_qtd', 'n_mais_um'] as $c) {
            $base[$c] = (int) $base[$c] + (int) $mais[$c];
        }

        foreach (['soma_ms', 'soma_sql_ms'] as $c) {
            $base[$c] = (float) $base[$c] + (float) $mais[$c];
        }

        foreach (['max_ms', 'max_memoria_mb'] as $c) {
            $base[$c] = max((float) $base[$c], (float) $mais[$c]);
        }

        $faixasMais = is_string($mais['faixas']) ? (json_decode($mais['faixas'], true) ?: []) : $mais['faixas'];
        $base['faixas'] = Histograma::somar($base['faixas'], $faixasMais);

        return $base;
    }
}
