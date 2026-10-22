<?php

namespace App\Filament\Egap\Externo\Groups\ServicosGerais\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Situação de uma resposta individual do questionário (legado:
 * ava_respostas.status). No legado os valores eram 0=pendente,
 * 2=aguardando validação, 3=invalidada, 4=validada.
 */
enum StatusResposta: string implements HasColor, HasLabel
{
    case Pendente = 'pendente';
    case AguardandoValidacao = 'aguardando_validacao';
    case Invalidada = 'invalidada';
    case Validada = 'validada';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::AguardandoValidacao => 'Aguardando Validação',
            self::Invalidada => 'Invalidada',
            self::Validada => 'Validada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pendente => 'gray',
            self::AguardandoValidacao => 'warning',
            self::Invalidada => 'danger',
            self::Validada => 'success',
        };
    }
}
