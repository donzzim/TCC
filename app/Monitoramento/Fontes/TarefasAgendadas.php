<?php

namespace App\Monitoramento\Fontes;

use App\Monitoramento\Coleta\OuvinteDoAgendador;
use Cron\CronExpression;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Throwable;

final class TarefasAgendadas
{
    private const TOLERANCIA_SEG = 120;

    public static function listar(array $batimentos): array
    {
        rescue(fn () => app(Kernel::class)->bootstrap(), null, false);

        $agora = now();
        $tarefas = [];

        foreach (app(Schedule::class)->events() as $evento) {
            $nome = OuvinteDoAgendador::nome($evento);
            $batimento = $batimentos[$nome] ?? null;
            $anteriorEsperada = self::anteriorEsperada($evento, $agora);

            $tarefas[$nome] = [
                'tarefa' => $nome,
                'expressao' => $evento->expression,
                'proxima' => self::seguro(fn () => $evento->nextRunDate($agora)->format('d/m H:i')),
                'ultima' => $batimento['ultima_execucao'] ?? null,
                'sucesso' => $batimento['sucesso'] ?? null,
                'mensagem' => $batimento['mensagem'] ?? null,
                'situacao' => self::situacao($batimento, $anteriorEsperada),
            ];
        }

        return array_values($tarefas);
    }

    private static function situacao(?array $batimento, ?int $anteriorEsperada): string
    {
        if ($batimento === null) {
            return 'sem registro';
        }

        if (! $batimento['sucesso']) {
            return 'falhou';
        }

        if ($anteriorEsperada !== null && $batimento['ultima_execucao'] < $anteriorEsperada) {
            return 'atrasada';
        }

        return 'ok';
    }

    private static function anteriorEsperada(Event $evento, $agora): ?int
    {
        return self::seguro(fn (): int => (new CronExpression($evento->expression))
            ->getPreviousRunDate($agora->copy()->subSeconds(self::TOLERANCIA_SEG)->setTimezone($evento->timezone ?? config('app.timezone')), 0, true)
            ->getTimestamp());
    }

    private static function seguro(callable $f): mixed
    {
        try {
            return $f();
        } catch (Throwable) {
            return null;
        }
    }
}
