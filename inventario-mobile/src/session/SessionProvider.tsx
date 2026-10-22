import { useQueryClient } from '@tanstack/react-query';
import {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useMemo,
    useState,
    type ReactNode,
} from 'react';
import { Platform } from 'react-native';

import { ApiError, onUnauthorized, setAuthToken } from '@/src/api/client';
import { sessaoApi } from '@/src/api/sessao';
import type { Sessao } from '@/src/api/types';

import { limparToken, lerToken, salvarToken } from './tokenStorage';

type Status = 'carregando' | 'anonimo' | 'autenticado';

interface SessionValue {
    status: Status;
    sessao?: Sessao;
    entrar: (login: string, senha: string) => Promise<void>;
    trocarSetor: (id: number) => Promise<void>;
    sair: () => Promise<void>;
}

const SessionContext = createContext<SessionValue | null>(null);

export function SessionProvider({ children }: { children: ReactNode }) {
    const queryClient = useQueryClient();
    const [status, setStatus] = useState<Status>('carregando');
    const [sessao, setSessao] = useState<Sessao | undefined>();

    const descartar = useCallback(async () => {
        setAuthToken(null);
        await limparToken();
        queryClient.clear();
        setSessao(undefined);
        setStatus('anonimo');
    }, [queryClient]);

    useEffect(() => {
        onUnauthorized(() => {
            void descartar();
        });

        return () => onUnauthorized(null);
    }, [descartar]);

    useEffect(() => {
        let ativo = true;

        (async () => {
            const salvo = await lerToken();

            if (!salvo) {
                if (ativo) setStatus('anonimo');
                return;
            }

            setAuthToken(salvo);

            try {
                const atual = await sessaoApi.atual();
                if (ativo) {
                    setSessao(atual);
                    setStatus('autenticado');
                }
            } catch (erro) {
                // 401 já é tratado por onUnauthorized; rede/5xx não invalidam o token.
                if (ativo && !(erro instanceof ApiError && erro.status === 401)) {
                    setStatus('anonimo');
                }
            }
        })();

        return () => {
            ativo = false;
        };
    }, []);

    const entrar = useCallback(async (login: string, senha: string) => {
        const resposta = await sessaoApi.login(login, senha, Platform.OS);

        setAuthToken(resposta.token);
        await salvarToken(resposta.token);
        setSessao(resposta.sessao);
        setStatus('autenticado');
    }, []);

    const trocarSetor = useCallback(
        async (id: number) => {
            const atual = await sessaoApi.trocarSetor(id);

            setSessao(atual);
            await queryClient.invalidateQueries();
        },
        [queryClient],
    );

    const sair = useCallback(async () => {
        try {
            await sessaoApi.logout();
        } catch {
            // sai localmente mesmo se o servidor não responder
        }
        await descartar();
    }, [descartar]);

    const value = useMemo(
        () => ({ status, sessao, entrar, trocarSetor, sair }),
        [status, sessao, entrar, trocarSetor, sair],
    );

    return <SessionContext.Provider value={value}>{children}</SessionContext.Provider>;
}

export function useSessao(): SessionValue {
    const ctx = useContext(SessionContext);

    if (!ctx) {
        throw new Error('useSessao deve ser usado dentro de SessionProvider');
    }

    return ctx;
}
