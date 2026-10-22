<?php

namespace App\Filament\Egap\Externo\Groups\ServicosGerais\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Situação de um formulário de avaliação (legado: ava_formulario.status).
 *
 * O legado usava de 6 a 8 códigos numéricos cujo significado mudava
 * ligeiramente (e às vezes de forma inconsistente, ex.: avafiscal.php grava
 * status 13 como "Tácito" para telefonia/copeiragem enquanto avatacito.php
 * sempre grava 8) de arquivo para arquivo. Este enum consolida isso em um
 * conjunto único e coerente; "com desconto" vs "sem desconto com ressalva"
 * do legado não viraram status separados aqui — ver nota na proposta de
 * arquitetura, é uma decisão a confirmar com quem entende a regra de glosa.
 */
enum StatusFormulario: string implements HasColor, HasLabel
{
    case Aberto = 'aberto';
    case AguardandoAvaliacaoGestor = 'aguardando_avaliacao_gestor';
    case Validado = 'validado';
    case Invalidado = 'invalidado';
    case Tacito = 'tacito';

    public function getLabel(): string
    {
        return match ($this) {
            self::Aberto => 'Aberto',
            self::AguardandoAvaliacaoGestor => 'Aguardando Avaliação do Gestor',
            self::Validado => 'Validado',
            self::Invalidado => 'Invalidado',
            self::Tacito => 'Tácito',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Aberto => 'gray',
            self::AguardandoAvaliacaoGestor => 'warning',
            self::Validado => 'success',
            self::Invalidado => 'danger',
            self::Tacito => 'info',
        };
    }
}
