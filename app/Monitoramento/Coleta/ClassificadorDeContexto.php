<?php

namespace App\Monitoramento\Coleta;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class ClassificadorDeContexto
{
    private const PREFIXOS = [
        'mobile-api' => ['mobile-api', 'mobile-api/*'],
        'egap-externo' => ['egap/externo', 'egap/externo/*', 'emes/egap/externo', 'emes/egap/externo/*'],
        'egap' => ['egap', 'egap/*', 'emes/egap', 'emes/egap/*'],
        'admin' => ['admin', 'admin/*', 'emes/admin', 'emes/admin/*'],
        'app' => ['app', 'app/*', 'emes/app', 'emes/app/*'],
    ];

    private const IGNORADOS = [
        '_debugbar*', '*/_debugbar*',
        'livewire/livewire*.js', '*/livewire/livewire*.js',
        'filament/assets*', '*/filament/assets*',
        'favicon.ico',
        '*/admin-egap/monitoramento*',
    ];

    public static function contexto(Request $r): string
    {
        $path = self::ehLivewire($r)
            ? (self::componentes($r)[0]['path'] ?? '')
            : $r->path();

        return self::contextoDoPath(trim((string) $path, '/'));
    }

    public static function rota(Request $r): string
    {
        if (self::ehLivewire($r)) {
            $nomes = array_column(self::componentes($r), 'name');

            return Str::limit('livewire:'.implode(',', $nomes), 250, '');
        }

        $rota = $r->route();

        if ($rota === null) {
            return '(sem rota)';
        }

        return Str::limit($rota->getName() ?: $rota->uri(), 250, '');
    }

    public static function deveIgnorar(Request $r): bool
    {
        if ($r->is(...self::IGNORADOS)) {
            return true;
        }

        if (! self::ehLivewire($r)) {
            return false;
        }

        $nomes = array_column(self::componentes($r), 'name');

        return $nomes !== [] && collect($nomes)->every(fn (string $nome): bool => str_contains($nome, '.monitoramento.'));
    }

    public static function ehLivewire(Request $r): bool
    {
        return $r->is('livewire/update', '*/livewire/update') || $r->headers->has('X-Livewire');
    }

    public static function componentes(Request $r): array
    {
        $componentes = $r->input('components', []);

        if (! is_array($componentes)) {
            return [];
        }

        $resultado = [];

        foreach ($componentes as $componente) {
            if (! is_array($componente)) {
                continue;
            }

            $snapshot = $componente['snapshot'] ?? null;
            $snapshot = is_string($snapshot) ? json_decode($snapshot, true) : $snapshot;
            $memo = is_array($snapshot) ? ($snapshot['memo'] ?? []) : [];

            if (is_array($memo) && is_string($memo['name'] ?? null) && $memo['name'] !== '') {
                $resultado[] = [
                    'name' => $memo['name'],
                    'path' => is_string($memo['path'] ?? null) ? $memo['path'] : '',
                ];
            }
        }

        return $resultado;
    }

    private static function contextoDoPath(string $path): string
    {
        foreach (self::PREFIXOS as $contexto => $padroes) {
            if (Str::is($padroes, $path)) {
                return $contexto;
            }
        }

        return 'outros';
    }
}
