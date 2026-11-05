<?php

namespace App\Monitoramento\Fontes;

use Illuminate\Database\ConnectionInterface;

final class ConsultasDoServidor extends Fonte
{
    public static function topPorTempo(int $limite = 20, ?ConnectionInterface $conexao = null): ?array
    {
        return self::seguro(fn (): array => array_map(fn (object $c): array => [
            'sql' => mb_strimwidth((string) $c->sql_texto, 0, 600, '...'),
            'execucoes' => (int) $c->execucoes,
            'tempo_total_ms' => round((float) $c->tempo_total_ps / 1e9, 1),
            'media_ms' => round((float) $c->media_ps / 1e9, 2),
            'linhas_examinadas' => (int) $c->linhas_examinadas,
            'linhas_retornadas' => (int) $c->linhas_retornadas,
        ], self::emes($conexao)->select(
            'select DIGEST_TEXT as sql_texto, COUNT_STAR as execucoes, SUM_TIMER_WAIT as tempo_total_ps,
                    AVG_TIMER_WAIT as media_ps, SUM_ROWS_EXAMINED as linhas_examinadas, SUM_ROWS_SENT as linhas_retornadas
               from performance_schema.events_statements_summary_by_digest
              where SCHEMA_NAME = database() and DIGEST_TEXT is not null
              order by SUM_TIMER_WAIT desc
              limit ?',
            [$limite]
        )));
    }
}
