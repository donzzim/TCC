<?php

namespace App\Filament\Egap\Externo\Groups\PlanoContratacao\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Etapas do fluxo do Plano de Contratação (coluna `id_etapa`, que referencia
 * `ped_situacao`). Os ids são os mesmos usados no legado
 * (`pla_novasolicitacao.api.php`, `pla_planodecontratacao.api.php`,
 * `pla_telaadministrador.api.php`, `pla_calendario.api.php`).
 *
 * Fluxo: Rascunho -> Em Análise -> (Devolvido -> Em Análise)* -> Concluído
 * -> Aguardando Confirmação -> Validado.
 */
enum SituacaoPlano: int implements HasColor, HasLabel
{
    case EmAnalise = 6;
    case Validado = 7;
    case RascunhoLegado = 9;
    case AguardandoConfirmacao = 10;
    case Devolvido = 11;
    case Concluido = 12;
    case Rascunho = 13;

    public function getLabel(): string
    {
        // Rótulos alinhados a `ped_situacao.Descricao`: 13 = "Em revisão"
        // (estado que "Salvar para Edição" gera), 6/7/11/12 idem.
        return match ($this) {
            self::EmAnalise => 'Em Análise',
            self::Validado => 'Validado',
            self::RascunhoLegado => 'Em Revisão (legado)',
            self::Rascunho => 'Em Revisão',
            self::AguardandoConfirmacao => 'Aguardando Confirmação',
            self::Devolvido => 'Devolvido',
            self::Concluido => 'Concluído',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::EmAnalise, self::AguardandoConfirmacao => 'warning',
            self::Validado, self::Concluido => 'success',
            self::Devolvido => 'info',
            self::RascunhoLegado, self::Rascunho => 'gray',
        };
    }

    /** Etapas em que o solicitante pode editar e reenviar o plano. */
    public static function editaveis(): array
    {
        return [self::RascunhoLegado->value, self::Devolvido->value, self::Rascunho->value];
    }

    /** Etapas em que o solicitante confirma a contratação (datas/SEI). */
    public static function confirmaveis(): array
    {
        return [self::AguardandoConfirmacao->value, self::Concluido->value];
    }

    /** Etapas em que os itens do plano podem ser apenas visualizados. */
    public static function visualizaveis(): array
    {
        return [self::EmAnalise->value, self::Validado->value, self::Concluido->value];
    }

    /** Opções (id => rótulo) para filtros de "Meus Planos" / Análise. */
    public static function opcoesFiltro(): array
    {
        return [
            self::EmAnalise->value => self::EmAnalise->getLabel(),
            self::Devolvido->value => self::Devolvido->getLabel(),
            self::Concluido->value => self::Concluido->getLabel(),
            self::Validado->value => self::Validado->getLabel(),
        ];
    }

    public static function rotulo(?int $id): string
    {
        return self::tryFrom((int) $id)?->getLabel() ?? '—';
    }
}
