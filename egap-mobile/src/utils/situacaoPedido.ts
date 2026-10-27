import type { Palette } from '@/src/theme/tokens';

/** Cor do selo por id de egap_ped_situacao (mesmo critério do portal, ValidarPedidoResource::corSituacao). */
export function corDaSituacao(id: number | string | null | undefined, colors: Palette): string {
    switch (Number(id)) {
        case 4: // Cancelado
        case 5: // Invalidado
            return colors.danger;
        case 6: // Em análise
        case 13: // Em revisão
        case 11: // Devolvido
            return colors.warning;
        case 3: // Atendido
        case 12: // Concluido
            return colors.success;
        case 7: // Validado
        case 8: // Agendado
        case 9: // Em atendimento
            return colors.primary;
        default:
            return colors.textMuted;
    }
}
