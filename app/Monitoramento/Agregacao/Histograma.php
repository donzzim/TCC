<?php

namespace App\Monitoramento\Agregacao;

final class Histograma
{
    public const FAIXAS = [50, 100, 200, 300, 500, 750, 1000, 1500, 2000, 3000, 5000, 10000, PHP_INT_MAX];

    public static function faixaDe(float $ms): int
    {
        foreach (self::FAIXAS as $indice => $limite) {
            if ($ms <= $limite) {
                return $indice;
            }
        }

        return count(self::FAIXAS) - 1;
    }

    public static function vazio(): array
    {
        return array_fill(0, count(self::FAIXAS), 0);
    }

    public static function somar(array $a, array $b): array
    {
        $soma = self::vazio();

        foreach ($soma as $indice => $_) {
            $soma[$indice] = (int) ($a[$indice] ?? 0) + (int) ($b[$indice] ?? 0);
        }

        return $soma;
    }

    public static function percentil(array $faixas, float $p): ?float
    {
        $total = array_sum($faixas);

        if ($total <= 0) {
            return null;
        }

        $alvo = max(1, (int) ceil($p * $total));
        $acumulado = 0;
        $ultima = count(self::FAIXAS) - 1;

        foreach (self::FAIXAS as $indice => $limite) {
            $acumulado += (int) ($faixas[$indice] ?? 0);

            if ($acumulado >= $alvo) {
                return $indice === $ultima ? (float) self::FAIXAS[$ultima - 1] : (float) $limite;
            }
        }

        return (float) self::FAIXAS[$ultima - 1];
    }
}
