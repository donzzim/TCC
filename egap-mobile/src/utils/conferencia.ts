import type { ConferenciaResumo } from '@/src/api/types';

/** Progresso da conferência (0–100): bens já tratados / total. Chega a 100 junto com `pode_finalizar`. */
export function progressoConferencia(resumo: ConferenciaResumo | null | undefined): number {
    if (!resumo || resumo.total <= 0) return 0;
    return ((resumo.total - resumo.pendentes) / resumo.total) * 100;
}
