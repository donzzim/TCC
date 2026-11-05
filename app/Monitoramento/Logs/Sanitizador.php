<?php

namespace App\Monitoramento\Logs;

final class Sanitizador
{
    private const REGRAS = [
        '/(Connection: [\w.-]+), SQL: .*?\)(?=\s+at\s|\s*$|"|\s*\{|\s*\[)/s' => '$1, SQL omitida)',
        '/\b\d+\|[A-Za-z0-9]{40,}\b/' => '***',
        '/Authorization:\s*\S+\s+\S+/i' => 'Authorization: ***',
        '/Bearer\s+\S+/i' => 'Bearer ***',
        '/("?\b[\w-]*(?:password|senha|token|secret|api_key)[\w-]*"?\s*[:=]\s*)("(?:[^"\\\\]|\\\\.)*"|[^\s,&}\]]+)/i' => '$1"***"',
        '/\b\d{3}\.\d{3}\.\d{3}-\d{2}\b/' => '***.***.***-**',
        '/(?<![\d.\-\/])\d{11}(?![\d.\-\/])/' => '***.***.***-**',
    ];

    public static function limpar(string $texto): string
    {
        return (string) preg_replace(array_keys(self::REGRAS), array_values(self::REGRAS), $texto);
    }
}
