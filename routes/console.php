<?php

use App\Services\QueueService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule::command('app:processar-aprovacoes')->everyFiveSeconds();

//Disparar lembrete todo dia as 6 da manhã para os cursos que estão a 3 dias e 1 dia do início

// Schedule::command('app:disparar-lembretes')->dailyAt('06:00');

// Lembretes de prazo do inventário (7 e 1 dia) para quem ainda não finalizou o setor
Schedule::command('egap:inventario-lembretes')->dailyAt('07:00');

Schedule::call(function () {
    app(QueueService::class)->ensureRunning();
})->everyFifteenMinutes();
