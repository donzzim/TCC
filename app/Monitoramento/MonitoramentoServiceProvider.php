<?php

namespace App\Monitoramento;

use App\Monitoramento\Armazenamento\RepositorioDeMetricas;
use App\Monitoramento\Armazenamento\RepositorioDeMetricasSql;
use App\Monitoramento\Coleta\BufferDeRequisicoes;
use App\Monitoramento\Coleta\OuvinteDoAgendador;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Support\Facades\Event;
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
        Event::listen(ScheduledTaskFinished::class, [OuvinteDoAgendador::class, 'concluida']);
        Event::listen(ScheduledTaskFailed::class, [OuvinteDoAgendador::class, 'falhou']);

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstalarMonitoramento::class,
                ColetarMetricas::class,
            ]);
        }
    }
}
