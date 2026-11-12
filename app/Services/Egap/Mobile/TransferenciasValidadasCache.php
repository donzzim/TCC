<?php

namespace App\Services\Egap\Mobile;

use App\Models\egap\Patrimonio\BensMoveis\ArquivoDigital;
use App\Models\egap\Patrimonio\BensMoveis\TransferenciaBemMovel;
use Closure;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TransferenciasValidadasCache
{
    public const SEGUNDOS = 120;

    private const CHAVE_VERSAO = 'egap_transferencias_validadas:versao';

    public static function registrarInvalidacao(): void
    {
        foreach ([ArquivoDigital::class, TransferenciaBemMovel::class] as $model) {
            $model::saved(fn () => self::invalidar());
            $model::deleted(fn () => self::invalidar());
        }
    }

    /**
     * @param  Closure(): array<int, int>  $consulta
     * @return array<int, int>
     */
    public static function lembrar(int $unidade, int $setor, Closure $consulta): array
    {
        $versao = self::store()->get(self::CHAVE_VERSAO, '0');

        return self::store()->remember(
            "egap_transferencias_validadas:{$versao}:{$unidade}|{$setor}",
            self::SEGUNDOS,
            $consulta,
        );
    }

    public static function invalidar(): void
    {
        self::store()->forever(self::CHAVE_VERSAO, (string) Str::uuid());
    }

    private static function store(): Repository
    {
        return Cache::store(config('cache.egap_store'));
    }
}
