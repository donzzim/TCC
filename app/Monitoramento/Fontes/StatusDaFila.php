<?php

namespace App\Monitoramento\Fontes;

use App\Services\QueueService;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;

final class StatusDaFila extends Fonte
{
    public static function ler(int $agora, ?ConnectionInterface $conexao = null): ?array
    {
        return self::seguro(function () use ($agora, $conexao): array {
            $db = self::emes($conexao);

            $porFila = $db->table('jobs')
                ->selectRaw('queue, count(*) as total')
                ->groupBy('queue')
                ->pluck('total', 'queue')
                ->map(fn ($total): int => (int) $total)
                ->all();

            $maisAntigo = $db->table('jobs')
                ->whereNull('reserved_at')
                ->where('available_at', '<=', $agora)
                ->min('available_at');

            return [
                'pendentes' => array_sum($porFila),
                'por_fila' => $porFila,
                'idade_mais_antigo_seg' => $maisAntigo === null ? null : max(0, $agora - (int) $maisAntigo),
                'falhos_total' => $db->table('failed_jobs')->count(),
                'falhos_24h' => $db->table('failed_jobs')
                    ->where('failed_at', '>=', Carbon::createFromTimestamp($agora, config('app.timezone'))->subDay())
                    ->count(),
                'worker' => self::seguro(fn (): array => app(QueueService::class)->status()),
            ];
        });
    }
}
