<?php

namespace App\Monitoramento\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InstalarMonitoramento extends Command
{
    public const CAMINHO_MIGRATIONS = 'database/migrations/monitoramento';

    protected $signature = 'monitoramento:instalar';

    protected $description = 'Cria o banco SQLite de métricas do monitoramento e suas tabelas';

    public function handle(): int
    {
        $arquivo = (string) config('database.connections.monitoramento.database');

        if ($arquivo !== ':memory:' && ! file_exists($arquivo)) {
            if (! is_dir(dirname($arquivo))) {
                mkdir(dirname($arquivo), 0775, true);
            }
            touch($arquivo);
            DB::purge('monitoramento');
        }

        return $this->call('migrate', [
            '--database' => 'monitoramento',
            '--path' => self::CAMINHO_MIGRATIONS,
            '--force' => true,
        ]);
    }
}
