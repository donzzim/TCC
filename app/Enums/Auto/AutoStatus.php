<?php

namespace App\Enums\Auto;

enum AutoStatus: string
{
    case ATIVO = 'ativo';
    case PAUSADO = 'pausado';
    case CANCELADO = 'cancelado';
    case AGUARDANDO_ACAO = 'aguardando_acao';
    case CONCLUIDO = 'concluido';
    case ERRO = 'erro';
}
