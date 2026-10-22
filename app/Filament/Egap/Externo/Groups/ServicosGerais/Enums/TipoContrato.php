<?php

namespace App\Filament\Egap\Externo\Groups\ServicosGerais\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Tipo do contrato terceirizado avaliado (legado: ava_contrato.id_tipo).
 *
 * 'li'/'co'/'te' usam o questionário de estrelas (ver {@see \App\Models\egap\ServicosGerais\Pergunta}
 * e {@see \App\Filament\Egap\Externo\Groups\ServicosGerais\Services\FormularioGeradorService}).
 * 'tr'/'ag' usam, no legado, um modelo de atestado por trabalhador/falta
 * (avatransporte_r.php + avatransporte.api.php, não migrado nesta versão —
 * {@see usaQuestionario()} retorna false para eles de propósito).
 */
enum TipoContrato: string implements HasLabel
{
    case Limpeza = 'li';
    case Copeiragem = 'co';
    case Telefonia = 'te';
    case Transporte = 'tr';
    case AtesteAgua = 'ag';

    public function getLabel(): string
    {
        return match ($this) {
            self::Limpeza => 'Limpeza',
            self::Copeiragem => 'Copeiragem',
            self::Telefonia => 'Telefonia',
            self::Transporte => 'Condução de Veículos',
            self::AtesteAgua => 'Ateste de Água',
        };
    }

    /**
     * Se este tipo usa o questionário de perguntas com estrelas (avc_perguntas
     * + avc_formulario_respostas). Os tipos que não usam (Transporte, Ateste
     * de Água) ainda não têm o modelo de atestado por trabalhador migrado.
     */
    public function usaQuestionario(): bool
    {
        return match ($this) {
            self::Limpeza, self::Copeiragem, self::Telefonia => true,
            self::Transporte, self::AtesteAgua => false,
        };
    }

    /**
     * Rótulos das duas ou três notas atribuídas pelo gestor ao período
     * (avc_periodos.nota_1/2/3), que variam por tipo de contrato (legado:
     * ava_periodo.ng1/ng2/ng3).
     *
     * @return array<int, string>
     */
    public function rotulosNotasPeriodo(): array
    {
        return match ($this) {
            self::Limpeza => [1 => 'NTR (Tempo de Resposta)', 2 => 'NAP (Assiduidade Pagamento)'],
            default => [
                1 => 'NTR (Tempo de Resposta)',
                2 => 'NOTF (Obrigações trabalhistas e fiscais)',
                3 => 'NDP (Documentação para pagamento)',
            ],
        };
    }
}
