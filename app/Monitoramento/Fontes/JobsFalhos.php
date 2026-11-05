<?php

namespace App\Monitoramento\Fontes;

use Illuminate\Database\ConnectionInterface;

final class JobsFalhos extends Fonte
{
    public static function recentes(int $limite = 50, ?ConnectionInterface $conexao = null): ?array
    {
        return self::seguro(fn (): array => self::emes($conexao)->table('failed_jobs')
            ->select(['uuid', 'queue', 'payload', 'exception', 'failed_at'])
            ->orderByDesc('failed_at')
            ->limit($limite)
            ->get()
            ->map(fn (object $j): array => [
                'uuid' => (string) $j->uuid,
                'fila' => (string) $j->queue,
                'job' => (string) (json_decode((string) $j->payload, true)['displayName'] ?? 'desconhecido'),
                'excecao' => mb_strimwidth(trim(strtok((string) $j->exception, "\n") ?: ''), 0, 300, '...'),
                'falhou_em' => (string) $j->failed_at,
            ])
            ->all());
    }
}
