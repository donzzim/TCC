<?php

namespace App\Monitoramento\Leitura;

use App\Monitoramento\Coleta\Snapshot;

final class SemaforoDeSaude
{
    private const PESO = ['verde' => 0, 'amarelo' => 1, 'vermelho' => 2];

    public static function avaliar(ResumoDoPeriodo $r, ?Snapshot $s, int $agora): array
    {
        $nivel = 'verde';
        $motivos = [];

        $acionar = function (string $novo, string $motivo) use (&$nivel, &$motivos): void {
            $motivos[] = $motivo;

            if (self::PESO[$novo] > self::PESO[$nivel]) {
                $nivel = $novo;
            }
        };

        if ($r->p95 !== null && $r->p95 > 3000) {
            $acionar('vermelho', 'p95 acima de 3 s');
        } elseif ($r->p95 !== null && $r->p95 > 1000) {
            $acionar('amarelo', 'p95 acima de 1 s');
        }

        $percentual = round($r->taxaErro5xx * 100, 1);
        $textoErro = rtrim(rtrim(number_format($percentual, 1, ',', ''), '0'), ',').'% de erros 5xx';

        if ($r->taxaErro5xx > 0.05) {
            $acionar('vermelho', $textoErro);
        } elseif ($r->taxaErro5xx > 0.01) {
            $acionar('amarelo', $textoErro);
        }

        if ($s === null) {
            $acionar('amarelo', 'nenhuma coleta registrada');

            return ['nivel' => $nivel, 'motivos' => $motivos];
        }

        if ($agora - $s->instante > 180) {
            $acionar('amarelo', 'coleta parada há '.intdiv($agora - $s->instante, 60).' min');
        }

        $fila = $s->fila;

        if ($fila !== null) {
            $idade = $fila['idade_mais_antigo_seg'] ?? null;

            if ($idade !== null && $idade > 600) {
                $acionar('amarelo', 'fila parada há '.intdiv((int) $idade, 60).' min');
            }

            if (($fila['pendentes'] ?? 0) > 0 && ($fila['worker']['running'] ?? null) === false) {
                $acionar('vermelho', 'worker da fila parado');
            }
        }

        $mysql = $s->mysql;

        if ($mysql !== null && ($mysql['max_connections'] ?? 0) > 0) {
            $uso = $mysql['threads_connected'] / $mysql['max_connections'];
            $texto = 'MySQL com '.round($uso * 100).'% das conexões em uso';

            if ($uso > 0.9) {
                $acionar('vermelho', $texto);
            } elseif ($uso > 0.7) {
                $acionar('amarelo', $texto);
            }
        }

        return ['nivel' => $nivel, 'motivos' => $motivos];
    }
}
