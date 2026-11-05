<?php

namespace App\Monitoramento\Coleta;

use App\Monitoramento\Fontes\StatusDaFila;
use App\Monitoramento\Fontes\StatusDoMysql;
use App\Monitoramento\Fontes\StatusDoServidor;
use App\Monitoramento\Fontes\UsuariosOnline;

final class ColetorDeSnapshot
{
    public static function capturar(int $agora): Snapshot
    {
        return new Snapshot(
            instante: $agora,
            online: UsuariosOnline::contar($agora),
            mysql: StatusDoMysql::ler(),
            fila: StatusDaFila::ler($agora),
            servidor: rescue(fn (): array => StatusDoServidor::ler(), null, false),
        );
    }
}
