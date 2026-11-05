<?php

namespace App\Monitoramento\Fontes;

use Closure;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Throwable;

abstract class Fonte
{
    protected static function emes(?ConnectionInterface $conexao = null): ConnectionInterface
    {
        return $conexao ?? DB::connection((string) config('monitoramento.conexao_leitura', 'emes'));
    }

    protected static function seguro(Closure $leitura): mixed
    {
        try {
            return $leitura();
        } catch (Throwable) {
            return null;
        }
    }
}
