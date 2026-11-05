<?php

namespace App\Monitoramento;

use App\Monitoramento\Armazenamento\RepositorioDeMetricas;
use App\Monitoramento\Armazenamento\RepositorioDeMetricasSql;
use App\Monitoramento\Coleta\BufferDeRequisicoes;
use App\Monitoramento\Console\ColetarMetricas;
use App\Monitoramento\Console\InstalarMonitoramento;
use Illuminate\Support\ServiceProvider;

class MonitoramentoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BufferDeRequisicoes::class, fn () => new BufferDeRequisicoes((string) config('monitoramento.buffer_dir')));
        $this->app->bind(RepositorioDeMetricas::class, fn () => new RepositorioDeMetricasSql('monitoramento'));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstalarMonitoramento::class,
                ColetarMetricas::class,
            ]);
        }
    }
}
