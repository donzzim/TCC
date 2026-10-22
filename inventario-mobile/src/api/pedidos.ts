import { api } from './client';
import type {
    MateriaisPagina,
    PedidoContexto,
    PedidoCriado,
    PedidoPayload,
    PedidosPagina,
    PedidoTipo,
} from './types';

export const pedidosApi = {
    listar: (page: number, perPage = 15) => api<PedidosPagina>('/pedidos', { query: { page, per_page: perPage } }),
    contexto: () => api<PedidoContexto>('/pedidos/contexto'),
    materiais: (tipo: PedidoTipo, search: string, page: number, perPage = 30) =>
        api<MateriaisPagina>('/pedidos/materiais', { query: { tipo, search: search.trim(), page, per_page: perPage } }),
    criar: (payload: PedidoPayload) => api<PedidoCriado>('/pedidos', { method: 'POST', body: payload }),
};
