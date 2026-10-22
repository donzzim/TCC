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
import { limparCarrinhos } from '@/src/cart/cartStorage';
import { limparRecentes } from '@/src/utils/recentes';
import type { Sessao } from '@/src/api/types';

import { limparToken, lerToken, salvarToken } from './tokenStorage';

type Status = 'carregando' | 'anonimo' | 'autenticado' | 'erro';

interface SessionValue {
    status: Status;
    sessao?: Sessao;
    entrar: (login: string, senha: string) => Promise<void>;
    trocarSetor: (id: number) => Promise<void>;
    sair: () => Promise<void>;
    /** Refaz a checagem inicial (GET /sessao) depois de status 'erro'. */
    recarregar: () => Promise<void>;
}

const SessionContext = createContext<SessionValue | null>(null);

export function SessionProvider({ children }: { children: ReactNode }) {
    const queryClient = useQueryClient();
    const [status, setStatus] = useState<Status>('carregando');
    const [sessao, setSessao] = useState<Sessao | undefined>();

    const descartar = useCallback(async () => {
        setAuthToken(null);
        await limparToken();
        await limparRecentes();
        await limparCarrinhos();
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

    const iniciar = useCallback(async () => {
        const salvo = await lerToken();

        if (!salvo) {
            setStatus('anonimo');
            return;
        }

        setAuthToken(salvo);

        try {
            const atual = await sessaoApi.atual();
            setSessao(atual);
            setStatus('autenticado');
        } catch (erro) {
            // 401 já é tratado por onUnauthorized (limpa o token e vai para anonimo).
            // Rede/5xx mantêm o token: as telas mostram o erro com "Tentar novamente".
            if (!(erro instanceof ApiError && erro.status === 401)) {
                setStatus('erro');
            }
        }
    }, []);

    useEffect(() => {
        void iniciar();
    }, [iniciar]);

    const recarregar = useCallback(async () => {
        setStatus('carregando');
        await iniciar();
    }, [iniciar]);

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
        () => ({ status, sessao, entrar, trocarSetor, sair, recarregar }),
        [status, sessao, entrar, trocarSetor, sair, recarregar],
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
