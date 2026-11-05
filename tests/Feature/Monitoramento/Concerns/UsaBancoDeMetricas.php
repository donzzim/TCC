<?php

namespace Tests\Feature\Monitoramento\Concerns;

use App\Monitoramento\Console\InstalarMonitoramento;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

trait UsaBancoDeMetricas
{
    protected function usarBancoDeMetricas(): void
    {
        config(['database.connections.monitoramento.database' => ':memory:']);
        DB::purge('monitoramento');

        Artisan::call('migrate', [
            '--database' => 'monitoramento',
            '--path' => InstalarMonitoramento::CAMINHO_MIGRATIONS,
            '--force' => true,
        ]);
    }
}
