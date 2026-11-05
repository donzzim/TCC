<?php

namespace App\Monitoramento\Armazenamento;

use App\Monitoramento\Agregacao\Histograma;
use App\Monitoramento\Coleta\RegistroDeRequisicao;
use App\Monitoramento\Coleta\Snapshot;
use App\Monitoramento\Leitura\LinhaDeRota;
use App\Monitoramento\Leitura\Periodo;
use App\Monitoramento\Leitura\PontoDaSerie;
use App\Monitoramento\Leitura\ResumoDoPeriodo;

trait LeituraSql
{
    public function resumo(Periodo $p, ?string $contexto, int $agora): ResumoDoPeriodo
    {
        $linhas = $this->linhasDoPeriodo($p, $contexto, $agora);
        $soma = $this->somar($linhas);

        return new ResumoDoPeriodo(
            qtd: $soma['qtd'],
            erros5xx: $soma['erros_5xx'],
            erros4xx: $soma['erros_4xx'],
            p50: Histograma::percentil($soma['faixas'], 0.5),
            p95: Histograma::percentil($soma['faixas'], 0.95),
            p99: Histograma::percentil($soma['faixas'], 0.99),
            reqPorMin: $soma['qtd'] / ($p->segundos() / 60),
            taxaErro5xx: $soma['qtd'] > 0 ? $soma['erros_5xx'] / $soma['qtd'] : 0.0,
        );
    }

    public function serieLatencia(Periodo $p, ?string $contexto, int $agora): array
    {
        return $this->serie($p, $contexto, $agora, function (array $linhas): array {
            $soma = $this->somar($linhas);

            return [
                'p50' => Histograma::percentil($soma['faixas'], 0.5),
                'p95' => Histograma::percentil($soma['faixas'], 0.95),
            ];
        });
    }

    public function serieVolume(Periodo $p, int $agora): array
    {
        $contextos = array_values(array_unique(array_column($this->linhasDoPeriodo($p, null, $agora), 'contexto')));
        sort($contextos);
        $minutosPorPasso = $p->passoSeg() / 60;

        return $this->serie($p, null, $agora, function (array $linhas) use ($contextos, $minutosPorPasso): array {
            $valores = array_fill_keys($contextos, 0.0);

            foreach ($linhas as $linha) {
                $valores[$linha->contexto] += $linha->qtd / $minutosPorPasso;
            }

            return $valores;
        });
    }

    public function serieErros(Periodo $p, ?string $contexto, int $agora): array
    {
        return $this->serie($p, $contexto, $agora, function (array $linhas): array {
            $soma = $this->somar($linhas);

            return ['5xx' => (float) $soma['erros_5xx'], '4xx' => (float) $soma['erros_4xx']];
        });
    }

    public function rotas(Periodo $p, ?string $contexto, string $ordem, int $limite, int $agora, bool $somenteLivewire = false): array
    {
        $grupos = [];

        foreach ($this->linhasDoPeriodo($p, $contexto, $agora) as $linha) {
            if ($somenteLivewire && ! str_starts_with($linha->rota, 'livewire:')) {
                continue;
            }

            $grupos[$linha->contexto."\0".$linha->rota][] = $linha;
        }

        $resultado = [];

        foreach ($grupos as $linhas) {
            $soma = $this->somar($linhas);
            $resultado[] = new LinhaDeRota(
                contexto: $linhas[0]->contexto,
                rota: $linhas[0]->rota,
                qtd: $soma['qtd'],
                p50: Histograma::percentil($soma['faixas'], 0.5),
                p95: Histograma::percentil($soma['faixas'], 0.95),
                maxMs: $soma['max_ms'],
                tempoTotalMs: $soma['soma_ms'],
                sqlMediaQtd: $soma['qtd'] > 0 ? $soma['soma_sql_qtd'] / $soma['qtd'] : 0.0,
                percentualSql: $soma['soma_ms'] > 0 ? min(100.0, $soma['soma_sql_ms'] / $soma['soma_ms'] * 100) : 0.0,
                maxMemoriaMb: $soma['max_memoria_mb'],
                nMaisUm: $soma['n_mais_um'],
                erros5xx: $soma['erros_5xx'],
            );
        }

        $chave = match ($ordem) {
            'p95' => fn (LinhaDeRota $l): float => (float) ($l->p95 ?? 0),
            'qtd' => fn (LinhaDeRota $l): float => (float) $l->qtd,
            'sql' => fn (LinhaDeRota $l): float => $l->sqlMediaQtd,
            default => fn (LinhaDeRota $l): float => $l->tempoTotalMs,
        };

        usort($resultado, fn (LinhaDeRota $a, LinhaDeRota $b): int => [$chave($b), $b->tempoTotalMs] <=> [$chave($a), $a->tempoTotalMs]);

        return array_slice($resultado, 0, $limite);
    }

    public function requisicoesLentas(Periodo $p, ?string $contexto, int $limite, int $agora): array
    {
        return $this->db()->table('requisicoes')
            ->where('instante', '>=', $agora - $p->segundos())
            ->where('instante', '<=', $agora)
            ->when($contexto !== null, fn ($q) => $q->where('contexto', $contexto))
            ->orderByDesc('instante')
            ->limit($limite)
            ->get()
            ->map(fn (object $linha): RegistroDeRequisicao => $this->registroDaLinha($linha))
            ->all();
    }

    public function requisicao(string $requestId): ?RegistroDeRequisicao
    {
        $linha = $this->db()->table('requisicoes')->where('request_id', $requestId)->first();

        return $linha === null ? null : $this->registroDaLinha($linha);
    }

    public function ultimoSnapshot(): ?Snapshot
    {
        $linha = $this->db()->table('snapshots')->orderByDesc('instante')->orderByDesc('id')->first();

        return $linha === null ? null : $this->snapshotDaLinha($linha);
    }

    public function serieSnapshots(Periodo $p, int $agora): array
    {
        $porPasso = [];

        foreach ($this->db()->table('snapshots')->whereBetween('instante', [$agora - $p->segundos(), $agora])->orderBy('instante')->get() as $linha) {
            $porPasso[intdiv((int) $linha->instante, $p->passoSeg())] = $linha;
        }

        return array_values(array_map(fn (object $linha): Snapshot => $this->snapshotDaLinha($linha), $porPasso));
    }

    public function batimentos(): array
    {
        $resultado = [];

        foreach ($this->db()->table('batimentos')->orderBy('tarefa')->get() as $linha) {
            $resultado[$linha->tarefa] = [
                'ultima_execucao' => (int) $linha->ultima_execucao,
                'sucesso' => (bool) $linha->sucesso,
                'mensagem' => $linha->mensagem,
            ];
        }

        return $resultado;
    }

    private function linhasDoPeriodo(Periodo $p, ?string $contexto, int $agora): array
    {
        $inicio = $agora - $p->segundos();
        $colunas = ['contexto', 'rota', 'qtd', 'erros_4xx', 'erros_5xx', 'soma_ms', 'max_ms', 'soma_sql_ms', 'soma_sql_qtd', 'max_memoria_mb', 'n_mais_um', 'faixas'];
        $filtrar = fn ($q) => $q->when($contexto !== null, fn ($q) => $q->where('contexto', $contexto));

        if (! $p->usaHoras()) {
            return $filtrar($this->db()->table('agregados_minuto')->select([...$colunas, 'minuto as t']))
                ->where('minuto', '>=', $inicio)
                ->where('minuto', '<', $agora)
                ->get()
                ->all();
        }

        $horaAtual = intdiv($agora, 3600) * 3600;

        $horas = $filtrar($this->db()->table('agregados_hora')->select([...$colunas, 'hora as t']))
            ->where('hora', '>=', $inicio)
            ->where('hora', '<', $horaAtual)
            ->get()
            ->all();

        $minutos = $filtrar($this->db()->table('agregados_minuto')->select([...$colunas, 'minuto as t']))
            ->where('minuto', '>=', $horaAtual)
            ->where('minuto', '<', $agora)
            ->get()
            ->all();

        return [...$horas, ...$minutos];
    }

    private function serie(Periodo $p, ?string $contexto, int $agora, callable $calcular): array
    {
        $passo = $p->passoSeg();
        $porPasso = [];

        foreach ($this->linhasDoPeriodo($p, $contexto, $agora) as $linha) {
            $porPasso[intdiv((int) $linha->t, $passo) * $passo][] = $linha;
        }

        $serie = [];

        for ($t = intdiv($agora - $p->segundos(), $passo) * $passo; $t <= $agora; $t += $passo) {
            $serie[] = new PontoDaSerie($t, $calcular($porPasso[$t] ?? []));
        }

        return $serie;
    }

    private function somar(array $linhas): array
    {
        $soma = [
            'qtd' => 0, 'erros_4xx' => 0, 'erros_5xx' => 0, 'soma_ms' => 0.0, 'max_ms' => 0.0,
            'soma_sql_ms' => 0.0, 'soma_sql_qtd' => 0, 'max_memoria_mb' => 0.0, 'n_mais_um' => 0,
            'faixas' => Histograma::vazio(),
        ];

        foreach ($linhas as $l) {
            $soma['qtd'] += (int) $l->qtd;
            $soma['erros_4xx'] += (int) $l->erros_4xx;
            $soma['erros_5xx'] += (int) $l->erros_5xx;
            $soma['soma_ms'] += (float) $l->soma_ms;
            $soma['max_ms'] = max($soma['max_ms'], (float) $l->max_ms);
            $soma['soma_sql_ms'] += (float) $l->soma_sql_ms;
            $soma['soma_sql_qtd'] += (int) $l->soma_sql_qtd;
            $soma['max_memoria_mb'] = max($soma['max_memoria_mb'], (float) $l->max_memoria_mb);
            $soma['n_mais_um'] += (int) $l->n_mais_um;
            $soma['faixas'] = Histograma::somar($soma['faixas'], json_decode($l->faixas, true) ?: []);
        }

        return $soma;
    }

    private function registroDaLinha(object $linha): RegistroDeRequisicao
    {
        return new RegistroDeRequisicao(
            instante: (float) $linha->instante,
            contexto: $linha->contexto,
            rota: $linha->rota,
            metodo: $linha->metodo,
            status: (int) $linha->status,
            duracaoMs: (float) $linha->duracao_ms,
            sqlMs: (float) $linha->sql_ms,
            sqlQtd: (int) $linha->sql_qtd,
            memoriaMb: (float) $linha->memoria_mb,
            livewire: json_decode((string) $linha->livewire, true) ?: [],
            nMaisUm: false,
            requestId: $linha->request_id,
            detalhe: $linha->detalhe === null ? null : (json_decode($linha->detalhe, true) ?: null),
        );
    }

    private function snapshotDaLinha(object $linha): Snapshot
    {
        return Snapshot::fromArray(['instante' => (int) $linha->instante, ...(json_decode($linha->dados, true) ?: [])]);
    }
}
