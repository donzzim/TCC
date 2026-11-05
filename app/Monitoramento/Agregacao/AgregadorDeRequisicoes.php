<?php

namespace App\Monitoramento\Agregacao;

use App\Monitoramento\Coleta\RegistroDeRequisicao;

final class AgregadorDeRequisicoes
{
    public static function agregar(iterable $registros): array
    {
        $grupos = [];
        $detalhados = [];

        foreach ($registros as $r) {
            $minuto = intdiv((int) $r->instante, 60) * 60;
            $chave = $minuto."\0".$r->contexto."\0".$r->rota;

            $grupos[$chave] ??= [
                'minuto' => $minuto, 'contexto' => $r->contexto, 'rota' => $r->rota,
                'qtd' => 0, 'erros4xx' => 0, 'erros5xx' => 0, 'somaMs' => 0.0, 'maxMs' => 0.0,
                'somaSqlMs' => 0.0, 'somaSqlQtd' => 0, 'maxMemoriaMb' => 0.0, 'nMaisUm' => 0,
                'faixas' => Histograma::vazio(),
            ];

            $g = &$grupos[$chave];
            $g['qtd']++;
            $g['erros4xx'] += $r->status >= 400 && $r->status < 500 ? 1 : 0;
            $g['erros5xx'] += $r->status >= 500 ? 1 : 0;
            $g['somaMs'] += $r->duracaoMs;
            $g['maxMs'] = max($g['maxMs'], $r->duracaoMs);
            $g['somaSqlMs'] += $r->sqlMs;
            $g['somaSqlQtd'] += $r->sqlQtd;
            $g['maxMemoriaMb'] = max($g['maxMemoriaMb'], $r->memoriaMb);
            $g['nMaisUm'] += $r->nMaisUm ? 1 : 0;
            $g['faixas'][Histograma::faixaDe($r->duracaoMs)]++;
            unset($g);

            if ($r->detalhe !== null) {
                $detalhados[] = $r;
            }
        }

        return [
            'agregados' => array_values(array_map(fn (array $g): AgregadoMinuto => new AgregadoMinuto(...$g), $grupos)),
            'detalhados' => $detalhados,
        ];
    }
}
