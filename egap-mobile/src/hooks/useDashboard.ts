import { useQuery } from '@tanstack/react-query';

import { dashboardApi } from '@/src/api/dashboard';
import { useSessao } from '@/src/session/SessionProvider';

export function useDashboard() {
    const { sessao } = useSessao();

    return useQuery({
        queryKey: ['dashboard', sessao?.setor_atual?.id ?? null],
        queryFn: dashboardApi.obter,
        staleTime: 60_000,
    });
}
