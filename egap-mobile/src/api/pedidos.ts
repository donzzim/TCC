import { api } from './client';
import type {
    MateriaisPagina,
    PedidoContexto,
    PedidoCriado,
    PedidoDetalhe,
    PedidoPayload,
    PedidosPagina,
    PedidoTipo,
} from './types';

export const pedidosApi = {
    listar: (page: number, perPage = 15) => api<PedidosPagina>('/pedidos', { query: { page, per_page: perPage } }),
    detalhe: (id: number | string) => api<{ pedido: PedidoDetalhe }>(`/pedidos/${id}`).then((r) => r.pedido),
    contexto: () => api<PedidoContexto>('/pedidos/contexto'),
    materiais: (tipo: PedidoTipo, search: string, page: number, perPage = 30) =>
        api<MateriaisPagina>('/pedidos/materiais', { query: { tipo, search: search.trim(), page, per_page: perPage } }),
    criar: (payload: PedidoPayload) => api<PedidoCriado>('/pedidos', { method: 'POST', body: payload }),
};
