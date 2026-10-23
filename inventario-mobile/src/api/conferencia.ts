import { api } from './client';
import type {
    ConferenciaAtual,
    ConferenciaBensPagina,
    ConferenciaFiltro,
    DivergenciaPayload,
    LeituraConferencia,
} from './types';

export const conferenciaApi = {
    atual: () => api<ConferenciaAtual>('/conferencia'),
    bens: (status: ConferenciaFiltro, page: number) =>
        api<ConferenciaBensPagina>('/conferencia/bens', {
            query: { status: status === 'todos' ? undefined : status, page, per_page: 30 },
        }),
    ler: (codigo: string) => api<LeituraConferencia>('/conferencia/leituras', { method: 'POST', body: { codigo } }),
    naoLocalizados: (bemIds: number[], justificativa: string) =>
        api<{ message?: string }>('/conferencia/nao-localizados', {
            method: 'POST',
            body: { bem_ids: bemIds, justificativa },
        }),
    divergencia: (payload: DivergenciaPayload) =>
        api<{ message?: string }>('/conferencia/divergencias', { method: 'POST', body: payload }),
    finalizar: () => api<{ message?: string }>('/conferencia/finalizar', { method: 'POST' }),
};
