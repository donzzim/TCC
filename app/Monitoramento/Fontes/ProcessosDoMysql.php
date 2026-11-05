<?php

namespace App\Monitoramento\Fontes;

use Illuminate\Database\ConnectionInterface;

final class ProcessosDoMysql extends Fonte
{
    public static function emExecucao(?ConnectionInterface $conexao = null): ?array
    {
        return self::seguro(fn (): array => array_map(fn (object $p): array => [
            'id' => (int) $p->id,
            'usuario' => (string) $p->usuario,
            'tempo_seg' => (int) $p->tempo_seg,
            'estado' => $p->estado,
            'sql' => $p->sql_texto === null ? null : mb_strimwidth((string) preg_replace('/\s+/', ' ', trim($p->sql_texto)), 0, 300, '...'),
        ], self::emes($conexao)->select(
            "select ID as id, USER as usuario, TIME as tempo_seg, STATE as estado, INFO as sql_texto
               from information_schema.processlist
              where COMMAND <> 'Sleep' and DB = database() and ID <> connection_id()
              order by TIME desc
              limit 100"
        )));
    }
}
