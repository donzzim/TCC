<?php

namespace App\Console\Commands\Egap;

use App\Services\Egap\Inventario\NotificadorInventario;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class LembrarPrazoInventario extends Command
{
    protected $signature = 'egap:inventario-lembretes {--data= : Data de referência (AAAA-MM-DD); padrão: hoje}';

    protected $description = 'Avisa os lotados dos setores cujo prazo de inventário vence em 7 ou 1 dia e que ainda não finalizaram';

    public function handle(): int
    {
        $data = $this->option('data');

        try {
            $hoje = $data ? CarbonImmutable::createFromFormat('!Y-m-d', (string) $data) : CarbonImmutable::today();
        } catch (\Throwable) {
            $hoje = null;
        }

        if (! $hoje) {
            $this->error('Data inválida: use o formato AAAA-MM-DD.');

            return self::FAILURE;
        }

        $enviados = NotificadorInventario::notificarLembretes($hoje);

        $this->info("Lembretes de prazo do inventário gravados: {$enviados}.");

        return self::SUCCESS;
    }
}
