<?php

namespace App\Monitoramento\Logs;

final class AgrupadorDeExcecoes
{
    private const NIVEIS = ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'];

    public static function agrupar(array $entradas): array
    {
        $grupos = [];

        foreach ($entradas as $e) {
            if (! in_array($e->nivel, self::NIVEIS, true)) {
                continue;
            }

            [$classe, $local] = self::identificar($e);
            $chave = $classe."\0".$local;

            if (! isset($grupos[$chave])) {
                $grupos[$chave] = [
                    'classe' => $classe,
                    'local' => $local,
                    'mensagem' => mb_strimwidth((string) ($e->contexto['message'] ?? $e->mensagem), 0, 300, '...'),
                    'qtd' => 0,
                    'primeira' => $e->data,
                    'ultima' => $e->data,
                    'url' => is_string($e->contexto['url'] ?? null) ? $e->contexto['url'] : null,
                ];
            }

            $grupos[$chave]['qtd']++;
            $grupos[$chave]['primeira'] = min($grupos[$chave]['primeira'], $e->data);
            $grupos[$chave]['ultima'] = max($grupos[$chave]['ultima'], $e->data);
        }

        $resultado = array_values($grupos);
        usort($resultado, fn (array $a, array $b): int => [$b['qtd'], $b['ultima']] <=> [$a['qtd'], $a['ultima']]);

        return $resultado;
    }

    private static function identificar(EntradaDeLog $e): array
    {
        $excecao = $e->contexto['exception'] ?? null;

        if (is_string($excecao) && preg_match('/^\[object\] \(([\w\\\\]+)\(code: [^)]*\): .* at (.+):(\d+)\)/s', $excecao, $m)) {
            return [$m[1], $m[2].':'.$m[3]];
        }

        if (is_string($excecao) && preg_match('/^[\w\\\\]+$/', $excecao)) {
            $local = isset($e->contexto['file'], $e->contexto['line']) ? $e->contexto['file'].':'.$e->contexto['line'] : '—';

            return [$excecao, $local];
        }

        if (preg_match('/([A-Z][\w]*(?:\\\\[A-Z][\w]*)+|\b[A-Z]\w*(?:Exception|Error))(?:: | at )/', $e->mensagem."\n".$e->trace, $m)) {
            $local = preg_match('/ at (\S+):(\d+)/', $e->mensagem."\n".$e->trace, $l) ? $l[1].':'.$l[2] : '—';

            return [$m[1], $local];
        }

        return ['(sem classe) '.mb_strimwidth($e->mensagem, 0, 80, '...'), '—'];
    }
}
