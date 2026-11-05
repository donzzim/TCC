<?php

namespace App\Monitoramento\Fontes;

final class ChecklistDeConfiguracao
{
    public static function avaliar(?array $servidor = null): array
    {
        $opcache = ($servidor ?? StatusDoServidor::ler())['opcache'] ?? null;
        $debug = (bool) config('app.debug');
        $ambiente = (string) app()->environment();
        $fila = (string) config('queue.default');
        $canaisDoLog = self::canaisDoLog();

        return [
            self::item('APP_DEBUG desligado fora de local', ! $debug || app()->environment('local'),
                $debug ? 'APP_DEBUG=true em "'.$ambiente.'": a tela de erro detalhada pode aparecer para usuários.' : 'APP_DEBUG=false'),
            self::item('Ambiente', true, $ambiente),
            self::item('Configuração em cache', app()->configurationIsCached(),
                'php artisan config:cache acelera cada requisição em produção.'),
            self::item('Rotas em cache', app()->routesAreCached(),
                'php artisan route:cache acelera o roteamento em produção.'),
            self::item('Debugbar desativado', ! config('debugbar.enabled'),
                config('debugbar.enabled') ? 'O debugbar coleta dados de toda requisição e expõe detalhes internos.' : 'desativado'),
            self::item('Fila assíncrona', $fila !== 'sync',
                $fila === 'sync' ? 'Com "sync", e-mails e notificações rodam dentro da requisição do usuário.' : 'driver "'.$fila.'"'),
            self::item('OPcache ativo', (bool) ($opcache['ativo'] ?? false),
                $opcache === null ? 'extensão não carregada' : (($opcache['ativo'] ?? false) ? 'ativo' : 'desligado neste processo')),
            self::item('Log com rotação', in_array('daily', $canaisDoLog, true),
                'canais: '.implode(', ', $canaisDoLog).'. Com "single" o arquivo cresce sem limite.'),
        ];
    }

    private static function item(string $titulo, bool $ok, string $detalhe): array
    {
        return ['titulo' => $titulo, 'ok' => $ok, 'detalhe' => $detalhe];
    }

    private static function canaisDoLog(): array
    {
        $padrao = (string) config('logging.default');
        $canal = config("logging.channels.{$padrao}", []);

        $nomes = ($canal['driver'] ?? null) === 'stack' ? (array) ($canal['channels'] ?? []) : [$padrao];

        return array_values(array_map(
            fn (string $nome): string => (string) (config("logging.channels.{$nome}.driver") ?? $nome),
            $nomes
        ));
    }
}
