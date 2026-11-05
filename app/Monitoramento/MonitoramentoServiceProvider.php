<?php

namespace App\Monitoramento;

use App\Monitoramento\Console\InstalarMonitoramento;
use Illuminate\Support\ServiceProvider;

class MonitoramentoServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstalarMonitoramento::class,
            ]);
        }
    }
}
