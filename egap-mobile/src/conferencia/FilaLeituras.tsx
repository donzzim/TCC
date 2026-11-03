import { useQueryClient } from '@tanstack/react-query';
import { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState, type ReactNode } from 'react';
import { AppState } from 'react-native';

import { ApiError } from '@/src/api/client';
import { conferenciaApi } from '@/src/api/conferencia';
import { useSessao } from '@/src/session/SessionProvider';
import { lerFilaSalva, salvarFila, type LeituraPendente } from './filaStorage';

/**
 * Leituras feitas sem rede (subsolo, almoxarifado) ficam guardadas no aparelho e são
 * enviadas quando a conexão volta, em ordem. Cada leitura guarda o setor em que foi
 * feita: o servidor grava no setor da sessão, então só sai quando a sessão estiver
 * nesse setor. Reenviar é seguro: o servidor responde "já conferido" a uma repetição.
 */

const INTERVALO_MS = 30_000;

export interface FalhaLeitura {
    codigo: string;
    mensagem: string;
}

interface FilaValue {
    /** Leituras do setor atual aguardando envio. */
    pendentes: LeituraPendente[];
    /** Leituras guardadas em outro setor (saem quando a sessão voltar para ele). */
    deOutroSetor: number;
    /** Recusadas pelo servidor no reenvio (ex.: setor finalizado), para o usuário ver. */
    falhas: FalhaLeitura[];
    enviando: boolean;
    adicionar: (codigo: string) => void;
    enviarAgora: () => Promise<void>;
    descartarFalhas: () => void;
}

const FilaContext = createContext<FilaValue | null>(null);


export function FilaLeiturasProvider({ children }: { children: ReactNode }) {
    const queryClient = useQueryClient();
    const { sessao, status } = useSessao();
    const setorId = sessao?.setor_atual?.id ?? null;
    const [fila, setFila] = useState<LeituraPendente[]>([]);
    const [falhas, setFalhas] = useState<FalhaLeitura[]>([]);
    const [enviando, setEnviando] = useState(false);
    const filaRef = useRef<LeituraPendente[]>([]);
    const ocupado = useRef(false);

    const atualizar = useCallback((nova: LeituraPendente[]) => {
        filaRef.current = nova;
        setFila(nova);
        void salvarFila(nova);
    }, []);

    const autenticado = status === 'autenticado';

    // carrega o que ficou da última vez; sem sessão a fila exposta é vazia (o logout apaga o salvo)
    useEffect(() => {
        if (!autenticado) {
            filaRef.current = [];
            return;
        }

        void lerFilaSalva().then((lida) => {
            filaRef.current = lida;
            setFila(lida);
            setFalhas([]);
        });
    }, [autenticado]);

    const adicionar = useCallback(
        (codigo: string) => {
            if (setorId === null) return;

            const repetida = filaRef.current.some((l) => l.codigo === codigo && l.setorId === setorId);

            if (!repetida) {
                atualizar([...filaRef.current, { codigo, setorId, em: Date.now() }]);
            }
        },
        [atualizar, setorId],
    );

    const enviarAgora = useCallback(async () => {
        if (ocupado.current || setorId === null) return;

        ocupado.current = true;
        setEnviando(true);
        let enviouAlguma = false;

        try {
            for (const leitura of filaRef.current.filter((l) => l.setorId === setorId)) {
                try {
                    const r = await conferenciaApi.ler(leitura.codigo);

                    // 200 também traz recusas (outro setor, não cadastrado...): sem a câmera aberta,
                    // o usuário só fica sabendo por aqui
                    if (!['localizado', 'relocalizado', 'ja_conferido'].includes(r.status)) {
                        setFalhas((atuais) => [...atuais, { codigo: leitura.codigo, mensagem: r.message }]);
                    }
                } catch (e) {
                    // ainda sem rede (ou servidor fora): para e tenta no próximo ciclo
                    if (e instanceof ApiError && (e.status === 0 || e.status >= 500)) break;

                    // recusada de vez (setor finalizado, código inválido...): sai da fila e aparece para o usuário
                    setFalhas((atuais) => [
                        ...atuais,
                        { codigo: leitura.codigo, mensagem: e instanceof ApiError ? e.message : 'Leitura recusada.' },
                    ]);
                }

                enviouAlguma = true;
                atualizar(filaRef.current.filter((l) => l !== leitura));
            }
        } finally {
            ocupado.current = false;
            setEnviando(false);

            if (enviouAlguma) {
                void queryClient.invalidateQueries({ queryKey: ['conferencia'] });
                void queryClient.invalidateQueries({ queryKey: ['dashboard'] });
            }
        }
    }, [atualizar, queryClient, setorId]);

    const visivel = useMemo(() => (autenticado ? fila : []), [autenticado, fila]);
    const pendentes = useMemo(() => visivel.filter((l) => l.setorId === setorId), [visivel, setorId]);
    const temPendentes = pendentes.length > 0;

    // tenta de tempos em tempos e sempre que o app volta para a frente
    useEffect(() => {
        if (!temPendentes) return;

        void enviarAgora();
        const timer = setInterval(() => void enviarAgora(), INTERVALO_MS);
        const assinatura = AppState.addEventListener('change', (estado) => {
            if (estado === 'active') void enviarAgora();
        });

        return () => {
            clearInterval(timer);
            assinatura.remove();
        };
    }, [temPendentes, enviarAgora]);

    const valor = useMemo<FilaValue>(
        () => ({
            pendentes,
            deOutroSetor: visivel.length - pendentes.length,
            falhas: autenticado ? falhas : [],
            enviando,
            adicionar,
            enviarAgora,
            descartarFalhas: () => setFalhas([]),
        }),
        [pendentes, visivel.length, autenticado, falhas, enviando, adicionar, enviarAgora],
    );

    return <FilaContext.Provider value={valor}>{children}</FilaContext.Provider>;
}

export function useFilaLeituras(): FilaValue {
    const valor = useContext(FilaContext);

    if (!valor) {
        throw new Error('useFilaLeituras precisa estar dentro de FilaLeiturasProvider');
    }

    return valor;
}
