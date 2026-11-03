import { apagarJson, gravarJson, lerJson } from '@/src/utils/armazenamentoLocal';

/** Leituras da conferência feitas sem rede, guardadas no aparelho (ver FilaLeituras). */
const CHAVE = 'egap_mobile_fila_leituras';

export interface LeituraPendente {
    codigo: string;
    setorId: number;
    em: number;
}

export async function lerFilaSalva(): Promise<LeituraPendente[]> {
    const valor = await lerJson(CHAVE);

    if (!Array.isArray(valor)) return [];

    return valor.filter(
        (l): l is LeituraPendente =>
            !!l && typeof l.codigo === 'string' && typeof l.setorId === 'number' && typeof l.em === 'number',
    );
}

export function salvarFila(fila: LeituraPendente[]): Promise<void> {
    return fila.length > 0 ? gravarJson(CHAVE, fila) : apagarJson(CHAVE);
}

/** Logout/401: leituras são do usuário e do setor dele; não passam para o próximo login. */
export function limparFilaLeituras(): Promise<void> {
    return apagarJson(CHAVE);
}
