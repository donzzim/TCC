<?php

namespace App\Monitoramento\Leitura;

enum Periodo: string
{
    case Q15 = '15m';
    case H1 = '1h';
    case H24 = '24h';
    case D7 = '7d';

    public function segundos(): int
    {
        return match ($this) {
            self::Q15 => 900,
            self::H1 => 3600,
            self::H24 => 86400,
            self::D7 => 604800,
        };
    }

    public function passoSeg(): int
    {
        return match ($this) {
            self::Q15, self::H1 => 60,
            self::H24 => 300,
            self::D7 => 3600,
        };
    }

    public function usaHoras(): bool
    {
        return $this === self::D7;
    }

    public function rotulo(): string
    {
        return match ($this) {
            self::Q15 => '15 min',
            self::H1 => '1 h',
            self::H24 => '24 h',
            self::D7 => '7 dias',
        };
    }

    public static function opcoes(): array
    {
        $opcoes = [];

        foreach (self::cases() as $caso) {
            $opcoes[$caso->value] = $caso->rotulo();
        }

        return $opcoes;
    }
}
