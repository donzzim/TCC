<?php

namespace App\Monitoramento\Fontes;

use Illuminate\Database\ConnectionInterface;

final class TabelasDoBanco extends Fonte
{
    public static function maiores(int $limite = 30, ?ConnectionInterface $conexao = null): ?array
    {
        return self::seguro(fn (): array => array_map(fn (object $t): array => [
            'tabela' => (string) $t->tabela,
            'motor' => (string) $t->motor,
            'linhas' => (int) $t->linhas,
            'dados_mb' => round((float) $t->dados / 1048576, 1),
            'indices_mb' => round((float) $t->indices / 1048576, 1),
        ], self::emes($conexao)->select(
            'select TABLE_NAME as tabela, coalesce(ENGINE, \'\') as motor, coalesce(TABLE_ROWS, 0) as linhas,
                    coalesce(DATA_LENGTH, 0) as dados, coalesce(INDEX_LENGTH, 0) as indices
               from information_schema.tables
              where TABLE_SCHEMA = database() and TABLE_TYPE = \'BASE TABLE\'
              order by coalesce(DATA_LENGTH, 0) + coalesce(INDEX_LENGTH, 0) desc
              limit ?',
            [$limite]
        )));
    }

    public static function myisam(?ConnectionInterface $conexao = null): ?array
    {
        return self::seguro(fn (): array => array_map(
            fn (object $t): string => (string) $t->tabela,
            self::emes($conexao)->select(
                "select TABLE_NAME as tabela from information_schema.tables
                  where TABLE_SCHEMA = database() and ENGINE = 'MyISAM'
                  order by TABLE_NAME"
            )
        ));
    }
}
