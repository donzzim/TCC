<?php

namespace App\Monitoramento\Fontes;

use Illuminate\Database\ConnectionInterface;

final class StatusDoMysql extends Fonte
{
    private const STATUS = [
        'Threads_connected' => 'threads_connected',
        'Threads_running' => 'threads_running',
        'Max_used_connections' => 'max_used_connections',
        'Questions' => 'questions',
        'Slow_queries' => 'slow_queries',
        'Uptime' => 'uptime',
        'Innodb_buffer_pool_read_requests' => 'innodb_buffer_pool_read_requests',
        'Innodb_buffer_pool_reads' => 'innodb_buffer_pool_reads',
        'Innodb_row_lock_waits' => 'innodb_row_lock_waits',
    ];

    private const VARIAVEIS = [
        'max_connections' => 'max_connections',
        'long_query_time' => 'long_query_time',
        'slow_query_log' => 'slow_query_log',
    ];

    public static function ler(?ConnectionInterface $conexao = null): ?array
    {
        return self::seguro(function () use ($conexao): array {
            $db = self::emes($conexao);
            $status = self::pares($db->select("SHOW GLOBAL STATUS WHERE Variable_name IN ('".implode("','", array_keys(self::STATUS))."')"));
            $variaveis = self::pares($db->select("SHOW GLOBAL VARIABLES WHERE Variable_name IN ('".implode("','", array_keys(self::VARIAVEIS))."')"));

            $resultado = [];

            foreach (self::STATUS as $nome => $chave) {
                $resultado[$chave] = (int) ($status[$nome] ?? 0);
            }

            $resultado['max_connections'] = (int) ($variaveis['max_connections'] ?? 0);
            $resultado['long_query_time'] = (float) ($variaveis['long_query_time'] ?? 0);
            $resultado['slow_query_log'] = strtoupper((string) ($variaveis['slow_query_log'] ?? '')) === 'ON';

            return $resultado;
        });
    }

    private static function pares(array $linhas): array
    {
        $pares = [];

        foreach ($linhas as $linha) {
            $linha = array_change_key_case((array) $linha, CASE_LOWER);
            $pares[$linha['variable_name']] = $linha['value'];
        }

        return $pares;
    }
}
