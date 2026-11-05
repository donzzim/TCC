<?php

namespace App\Monitoramento\Fontes;

use Composer\InstalledVersions;
use Illuminate\Database\ConnectionInterface;

final class StatusDoServidor extends Fonte
{
    public static function ler(?ConnectionInterface $conexao = null): array
    {
        [$branch, $commit] = self::git();

        return [
            'php' => PHP_VERSION,
            'laravel' => self::versao('laravel/framework'),
            'filament' => self::versao('filament/filament'),
            'livewire' => self::versao('livewire/livewire'),
            'mysql' => self::seguro(fn (): string => (string) self::emes($conexao)->selectOne('select version() as v')->v),
            'memory_limit' => (string) ini_get('memory_limit'),
            'max_execution_time' => (string) ini_get('max_execution_time'),
            'opcache' => self::opcache(),
            'disco_livre_bytes' => self::seguro(fn () => @disk_free_space(base_path()) ?: null),
            'disco_total_bytes' => self::seguro(fn () => @disk_total_space(base_path()) ?: null),
            'tamanho_logs_bytes' => self::tamanho(storage_path('logs'), false),
            'tamanho_app_storage_bytes' => self::tamanho(storage_path('app/monitoramento'), true),
            'commit' => $commit,
            'branch' => $branch,
        ];
    }

    private static function versao(string $pacote): string
    {
        return (string) (self::seguro(fn () => InstalledVersions::getPrettyVersion($pacote)) ?? '?');
    }

    private static function opcache(): ?array
    {
        if (! function_exists('opcache_get_status')) {
            return null;
        }

        $status = @opcache_get_status(false);

        if (! is_array($status)) {
            return ['ativo' => false, 'memoria_usada_mb' => 0.0, 'memoria_livre_mb' => 0.0, 'taxa_acerto' => 0.0, 'scripts' => 0];
        }

        return [
            'ativo' => (bool) ($status['opcache_enabled'] ?? false),
            'memoria_usada_mb' => round(($status['memory_usage']['used_memory'] ?? 0) / 1048576, 1),
            'memoria_livre_mb' => round(($status['memory_usage']['free_memory'] ?? 0) / 1048576, 1),
            'taxa_acerto' => round((float) ($status['opcache_statistics']['opcache_hit_rate'] ?? 0), 2),
            'scripts' => (int) ($status['opcache_statistics']['num_cached_scripts'] ?? 0),
        ];
    }

    private static function tamanho(string $pasta, bool $recursivo): int
    {
        if (! is_dir($pasta)) {
            return 0;
        }

        $arquivos = $recursivo
            ? new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($pasta, \FilesystemIterator::SKIP_DOTS))
            : new \FilesystemIterator($pasta);

        $total = 0;

        foreach ($arquivos as $arquivo) {
            if ($arquivo->isFile()) {
                $total += $arquivo->getSize();
            }
        }

        return $total;
    }

    private static function git(): array
    {
        $head = @file_get_contents(base_path('.git/HEAD'));

        if (! is_string($head)) {
            return [null, null];
        }

        $head = trim($head);

        if (! str_starts_with($head, 'ref: ')) {
            return [null, preg_match('/^[0-9a-f]{40}$/', $head) ? $head : null];
        }

        $ref = substr($head, 5);
        $branch = preg_replace('#^refs/heads/#', '', $ref);
        $commit = @file_get_contents(base_path('.git/'.$ref));

        if (! is_string($commit)) {
            $packed = (string) @file_get_contents(base_path('.git/packed-refs'));
            $commit = preg_match('/^([0-9a-f]{40}) '.preg_quote($ref, '/').'$/m', $packed, $m) ? $m[1] : null;
        }

        return [$branch, $commit === null ? null : trim($commit)];
    }
}
