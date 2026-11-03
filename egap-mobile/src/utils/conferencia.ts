import type { ConferenciaResumo } from '@/src/api/types';

/**
 * Progresso da conferência (0–100): bens já tratados / bens a conferir. Chega a 100 junto
 * com `pode_finalizar`. Em transferência e cadastrados sem termo nunca ficam pendentes, por
 * isso a base é `total_conferivel` e não `total` (com `total` o anel começava acima de 0%).
 */
export function progressoConferencia(resumo: ConferenciaResumo | null | undefined): number {
    if (!resumo) return 0;

    const base = resumo.total_conferivel ?? resumo.total;

    if (base <= 0) return 0;

    return Math.max(0, Math.min(100, ((base - resumo.pendentes) / base) * 100));
}
