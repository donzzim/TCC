import { api } from './client';
import type { NotificacoesPagina } from './types';

export const notificacoesApi = {
    listar: (page: number) => api<NotificacoesPagina>('/notificacoes', { query: { page } }),
    marcarLida: (id: string) => api<null>(`/notificacoes/${encodeURIComponent(id)}/lida`, { method: 'POST' }),
    marcarTodas: () => api<null>('/notificacoes/lidas', { method: 'POST' }),
};
