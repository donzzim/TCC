<?php

namespace App\Monitoramento;

use App\Monitoramento\Coleta\BufferDeRequisicoes;
use App\Monitoramento\Console\InstalarMonitoramento;
use Illuminate\Support\ServiceProvider;

class MonitoramentoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BufferDeRequisicoes::class, fn () => new BufferDeRequisicoes((string) config('monitoramento.buffer_dir')));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstalarMonitoramento::class,
            ]);
        }
    }
}
