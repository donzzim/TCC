import { useRouter } from 'expo-router';
import { useEffect } from 'react';

import { ApiError } from '@/src/api/client';

/** Falha de rede ou 5xx: leva para a tela de erro com "tentar novamente". */
export function useErroRede(error: unknown, origem: string): void {
    const router = useRouter();

    useEffect(() => {
        if (error instanceof ApiError && (error.status === 0 || error.status >= 500)) {
            router.push({ pathname: '/erro', params: { origem } });
        }
    }, [error, origem, router]);
}
