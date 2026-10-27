import { useInfiniteQuery, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { pedidosApi } from '@/src/api/pedidos';
import type { PedidoPayload, PedidoTipo } from '@/src/api/types';
import { useSessao } from '@/src/session/SessionProvider';

function useSetorId(): number | null {
    const { sessao } = useSessao();

    return sessao?.setor_atual?.id ?? null;
}

export function usePedidos(enabled = true) {
    const setorId = useSetorId();

    return useInfiniteQuery({
        queryKey: ['pedidos', setorId, 'lista'],
        enabled,
        queryFn: ({ pageParam }) => pedidosApi.listar(pageParam),
        initialPageParam: 1,
        getNextPageParam: (ultima) => (ultima.meta.has_more ? ultima.meta.current_page + 1 : undefined),
        staleTime: 30_000,
    });
}

/** Um pedido com o histórico; busca de novo a cada abertura (costuma vir de um aviso de atualização). */
export function usePedido(id: string | undefined) {
    return useQuery({
        queryKey: ['pedidos', 'detalhe', id],
        enabled: !!id,
        queryFn: () => pedidosApi.detalhe(id ?? ''),
        staleTime: 0,
    });
}

export function useMateriais(tipo: PedidoTipo, search: string) {
    const setorId = useSetorId();

    return useInfiniteQuery({
        queryKey: ['pedidos', setorId, 'materiais', tipo, search.trim()],
        queryFn: ({ pageParam }) => pedidosApi.materiais(tipo, search, pageParam),
        initialPageParam: 1,
        getNextPageParam: (ultima) => (ultima.meta.has_more ? ultima.meta.current_page + 1 : undefined),
        staleTime: 5 * 60_000,
    });
}

export function useContextoPedido() {
    const setorId = useSetorId();

    return useQuery({
        queryKey: ['pedidos', setorId, 'contexto'],
        queryFn: pedidosApi.contexto,
        staleTime: 5 * 60_000,
    });
}

export function useEnviarPedido() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: PedidoPayload) => pedidosApi.criar(payload),
        onSuccess: () =>
            Promise.all([
                queryClient.invalidateQueries({ queryKey: ['pedidos'] }),
                queryClient.invalidateQueries({ queryKey: ['dashboard'] }),
            ]),
    });
}
