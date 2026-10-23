import { api } from './client';
import type { Bem, Paginated } from './types';

export const bensApi = {
    listar: (search: string, page: number, perPage = 20) =>
        api<Paginated<Bem>>('/bens', { query: { search: search.trim(), page, per_page: perPage } }),
    obter: (patrimonio: string) => api<{ data: Bem }>(`/bens/${encodeURIComponent(patrimonio)}`),
};
