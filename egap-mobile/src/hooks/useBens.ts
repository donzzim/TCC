import { queryOptions, useInfiniteQuery, useQuery } from '@tanstack/react-query';

import { bensApi } from '@/src/api/bens';
import { useSessao } from '@/src/session/SessionProvider';

const CINCO_MIN = 5 * 60_000;

export function useBens(search: string) {
    const { sessao } = useSessao();

    return useInfiniteQuery({
        queryKey: ['bens', sessao?.setor_atual?.id ?? null, search.trim()],
        queryFn: ({ pageParam }) => bensApi.listar(search, pageParam),
        initialPageParam: 1,
        getNextPageParam: (ultima) => (ultima.meta.has_more ? ultima.meta.current_page + 1 : undefined),
        staleTime: CINCO_MIN,
    });
}

/** Opções compartilhadas (detalhe e leitura): a mesma chave de cache sempre guarda `Bem`. */
export function bemQueryOptions(setorId: number | null, patrimonio: string) {
    return queryOptions({
        queryKey: ['bem', setorId, patrimonio],
        queryFn: async () => (await bensApi.obter(patrimonio)).data,
        staleTime: CINCO_MIN,
    });
}

export function useBem(patrimonio: string | undefined) {
    const { sessao } = useSessao();

    return useQuery({
        ...bemQueryOptions(sessao?.setor_atual?.id ?? null, patrimonio ?? ''),
        enabled: !!patrimonio,
    });
}
