<?php

namespace App\Monitoramento\Coleta;

use App\Monitoramento\Armazenamento\RepositorioDeMetricas;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Scheduling\Event;

final class OuvinteDoAgendador
{
    public static function nome(Event $tarefa): string
    {
        $comando = (string) ($tarefa->command ?? '');

        if ($comando !== '') {
            $nome = trim((string) preg_replace('/^.*?artisan[\'"]?\s+/', '', $comando));

            if ($nome !== '') {
                return $nome;
            }
        }

        return (string) ($tarefa->description ?: 'tarefa sem nome');
    }

    public function concluida(ScheduledTaskFinished $evento): void
    {
        $this->registrar($evento->task, true, null);
    }

    public function falhou(ScheduledTaskFailed $evento): void
    {
        $this->registrar($evento->task, false, mb_strimwidth($evento->exception->getMessage(), 0, 300));
    }

    private function registrar(Event $tarefa, bool $sucesso, ?string $mensagem): void
    {
        if (! config('monitoramento.ativo')) {
            return;
        }

        rescue(fn () => app(RepositorioDeMetricas::class)->registrarBatimento(
            self::nome($tarefa),
            now()->getTimestamp(),
            $sucesso,
            $mensagem,
        ), null, false);
    }
}
