import { useInfiniteQuery, useMutation, useQueryClient, type InfiniteData } from '@tanstack/react-query';
import type { Href } from 'expo-router';

import { notificacoesApi } from '@/src/api/notificacoes';
import type { NotificacoesPagina } from '@/src/api/types';

const CHAVE = ['notificacoes'] as const;

function opcoes() {
    return {
        queryKey: CHAVE,
        queryFn: ({ pageParam }: { pageParam: number }) => notificacoesApi.listar(pageParam),
        initialPageParam: 1,
        getNextPageParam: (ultima: NotificacoesPagina) =>
            ultima.meta.has_more ? ultima.meta.current_page + 1 : undefined,
    };
}

export function useNotificacoes() {
    return useInfiniteQuery(opcoes());
}

/** Total de não lidas (vem no meta de qualquer página; usa a 1ª). Atualiza a cada minuto. */
export function useNaoLidas() {
    return useInfiniteQuery({
        ...opcoes(),
        refetchInterval: 60_000,
        select: (dados: InfiniteData<NotificacoesPagina, number>) => dados.pages[0]?.meta.nao_lidas ?? 0,
    });
}

export function useMarcarLida() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (id: string) => notificacoesApi.marcarLida(id),
        onSuccess: () => queryClient.invalidateQueries({ queryKey: CHAVE }),
    });
}

export function useMarcarTodas() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: () => notificacoesApi.marcarTodas(),
        onSuccess: () => queryClient.invalidateQueries({ queryKey: CHAVE }),
    });
}

/** Rotas do app que uma notificação pode abrir; qualquer outra é ignorada. */
const ROTAS_CONHECIDAS = new Set(['/tutorial', '/(tabs)/conferencia']);
const ROTA_PEDIDO = /^\/pedidos\/\d+$/;

export function rotaDaNotificacao(rota: string | null | undefined): Href | null {
    return rota && (ROTAS_CONHECIDAS.has(rota) || ROTA_PEDIDO.test(rota)) ? (rota as Href) : null;
}
