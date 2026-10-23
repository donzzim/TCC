import { useInfiniteQuery, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { conferenciaApi } from '@/src/api/conferencia';
import { ApiError } from '@/src/api/client';
import type { ConferenciaFiltro, DivergenciaPayload } from '@/src/api/types';
import { useSessao } from '@/src/session/SessionProvider';

function useSetorId(): number | null {
    const { sessao } = useSessao();

    return sessao?.setor_atual?.id ?? null;
}

export function useConferenciaAtual() {
    const setorId = useSetorId();

    return useQuery({
        queryKey: ['conferencia', setorId, 'atual'],
        queryFn: conferenciaApi.atual,
        staleTime: 30_000,
    });
}

export function useConferenciaBens(filtro: ConferenciaFiltro) {
    const setorId = useSetorId();

    return useInfiniteQuery({
        queryKey: ['conferencia', setorId, 'bens', filtro],
        queryFn: ({ pageParam }) => conferenciaApi.bens(filtro, pageParam),
        initialPageParam: 1,
        getNextPageParam: (ultima) => (ultima.meta.has_more ? ultima.meta.current_page + 1 : undefined),
        staleTime: 30_000,
    });
}

/** Mensagem de erro para exibir inline: junta as mensagens de validação (422) quando houver. */
export function mensagemErro(e: unknown, padrao: string): string {
    if (e instanceof ApiError) {
        const detalhes = Object.values(e.errors ?? {}).flat();

        return detalhes.length > 0 ? detalhes.join(' ') : e.message;
    }

    return padrao;
}

/** Toda mutation da conferência muda contagens do painel e da lista. */
function useInvalidarConferencia() {
    const queryClient = useQueryClient();

    return () =>
        Promise.all([
            queryClient.invalidateQueries({ queryKey: ['conferencia'] }),
            queryClient.invalidateQueries({ queryKey: ['dashboard'] }),
        ]);
}

export function useLerCodigo() {
    const invalidar = useInvalidarConferencia();

    return useMutation({
        mutationFn: (codigo: string) => conferenciaApi.ler(codigo),
        onSuccess: () => invalidar(),
    });
}

export function useNaoLocalizados() {
    const invalidar = useInvalidarConferencia();

    return useMutation({
        mutationFn: ({ bemIds, justificativa }: { bemIds: number[]; justificativa: string }) =>
            conferenciaApi.naoLocalizados(bemIds, justificativa),
        onSuccess: () => invalidar(),
    });
}

export function useDivergencia() {
    const invalidar = useInvalidarConferencia();

    return useMutation({
        mutationFn: (payload: DivergenciaPayload) => conferenciaApi.divergencia(payload),
        onSuccess: () => invalidar(),
    });
}

export function useFinalizarSetor() {
    const invalidar = useInvalidarConferencia();

    return useMutation({
        mutationFn: () => conferenciaApi.finalizar(),
        onSuccess: () => invalidar(),
    });
}
