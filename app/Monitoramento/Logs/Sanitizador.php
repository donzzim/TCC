<?php

namespace App\Monitoramento\Logs;

final class Sanitizador
{
    private const REGRAS = [
        '/\b\d+\|[A-Za-z0-9]{40,}\b/' => '***',
        '/Bearer\s+\S+/i' => 'Bearer ***',
        '/("?\b(?:password|senha|token|secret|api_key)\b"?\s*[:=]\s*)("(?:[^"\\\\]|\\\\.)*"|[^\s,&}\]]+)/i' => '$1"***"',
        '/\b\d{3}\.\d{3}\.\d{3}-\d{2}\b/' => '***.***.***-**',
        '/(?<![\d.\-\/])\d{11}(?![\d.\-\/])/' => '***.***.***-**',
    ];

    public static function limpar(string $texto): string
    {
        return (string) preg_replace(array_keys(self::REGRAS), array_values(self::REGRAS), $texto);
    }
}
