import {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useMemo,
    useState,
    type ReactNode,
} from 'react';

import type { Material, PedidoTipo } from '@/src/api/types';
import { useSessao } from '@/src/session/SessionProvider';

import { lerCarrinhoSalvo, salvarCarrinho } from './cartStorage';

export const QUANTIDADE_MAX = 5000;

export interface ItemCarrinho {
    materialId: number;
    descricao: string;
    unidade: string;
    quantidade: number;
    /** Só permanente. */
    tipoAtendimento: 'adicao' | 'substituicao';
    patrimonioSubstituido: string;
    justificativa: string;
}

export interface CarrinhoTipo {
    itens: ItemCarrinho[];
    complementoId: number | null;
    /** Justificativa geral (só consumo). */
    justificativa: string;
}

interface Estado {
    /** Setor do carrinho: trocar de setor descarta o carrinho (materiais e complementos são do setor). */
    setorId: number | null;
    consumo: CarrinhoTipo;
    permanente: CarrinhoTipo;
}

interface CartValue {
    hidratado: boolean;
    carrinho: (tipo: PedidoTipo) => CarrinhoTipo;
    adicionar: (tipo: PedidoTipo, material: Material, quantidade: number) => void;
    atualizarItem: (tipo: PedidoTipo, materialId: number, patch: Partial<ItemCarrinho>) => void;
    remover: (tipo: PedidoTipo, materialId: number) => void;
    definir: (tipo: PedidoTipo, patch: Partial<Pick<CarrinhoTipo, 'complementoId' | 'justificativa'>>) => void;
    limpar: (tipo: PedidoTipo) => void;
}

const VAZIO_TIPO: CarrinhoTipo = { itens: [], complementoId: null, justificativa: '' };
const VAZIO: Estado = { setorId: null, consumo: VAZIO_TIPO, permanente: VAZIO_TIPO };

const CartContext = createContext<CartValue | null>(null);

function normalizarTipo(bruto: unknown): CarrinhoTipo {
    const t = (bruto ?? {}) as Partial<CarrinhoTipo>;
    const itens = Array.isArray(t.itens)
        ? t.itens
              .filter((i) => i && typeof i.materialId === 'number' && typeof i.quantidade === 'number')
              .map((i) => ({
                  materialId: i.materialId,
                  descricao: String(i.descricao ?? ''),
                  unidade: String(i.unidade ?? 'Unidade'),
                  quantidade: Math.min(QUANTIDADE_MAX, Math.max(1, Math.trunc(i.quantidade))),
                  tipoAtendimento: i.tipoAtendimento === 'substituicao' ? ('substituicao' as const) : ('adicao' as const),
                  patrimonioSubstituido: String(i.patrimonioSubstituido ?? ''),
                  justificativa: String(i.justificativa ?? ''),
              }))
        : [];

    return {
        itens,
        complementoId: typeof t.complementoId === 'number' ? t.complementoId : null,
        justificativa: String(t.justificativa ?? ''),
    };
}

function normalizar(bruto: unknown): Estado {
    const e = (bruto ?? {}) as Partial<Estado>;

    return {
        setorId: typeof e.setorId === 'number' ? e.setorId : null,
        consumo: normalizarTipo(e.consumo),
        permanente: normalizarTipo(e.permanente),
    };
}

export function CartProvider({ children }: { children: ReactNode }) {
    const { status, sessao } = useSessao();
    const autenticado = status === 'autenticado';
    const setorAtual = sessao?.setor_atual?.id ?? null;
    const [estado, setEstado] = useState<Estado>(VAZIO);
    const [hidratado, setHidratado] = useState(false);

    // Hidrata ao autenticar; ao sair (logout/401) zera a memória (o storage é limpo pelo SessionProvider).
    useEffect(() => {
        if (!autenticado) {
            setHidratado(false);
            setEstado(VAZIO);
            return;
        }

        let ativo = true;

        void lerCarrinhoSalvo().then((salvo) => {
            if (!ativo) return;

            setEstado(normalizar(salvo));
            setHidratado(true);
        });

        return () => {
            ativo = false;
        };
    }, [autenticado]);

    // Carrinho de outro setor não vale para o setor atual.
    useEffect(() => {
        if (!hidratado || setorAtual === null) return;

        setEstado((atual) => (atual.setorId === setorAtual ? atual : { ...VAZIO, setorId: setorAtual }));
    }, [hidratado, setorAtual]);

    useEffect(() => {
        if (hidratado) {
            void salvarCarrinho(estado);
        }
    }, [estado, hidratado]);

    const alterar = useCallback((tipo: PedidoTipo, fn: (c: CarrinhoTipo) => CarrinhoTipo) => {
        setEstado((atual) => ({ ...atual, [tipo]: fn(atual[tipo]) }));
    }, []);

    const adicionar = useCallback<CartValue['adicionar']>(
        (tipo, material, quantidade) => {
            const qtd = Math.min(QUANTIDADE_MAX, Math.max(1, Math.trunc(quantidade)));

            alterar(tipo, (c) => {
                const existe = c.itens.some((i) => i.materialId === material.id);

                return {
                    ...c,
                    itens: existe
                        ? c.itens.map((i) => (i.materialId === material.id ? { ...i, quantidade: qtd } : i))
                        : [
                              ...c.itens,
                              {
                                  materialId: material.id,
                                  descricao: material.descricao,
                                  unidade: material.unidade,
                                  quantidade: qtd,
                                  tipoAtendimento: 'adicao',
                                  patrimonioSubstituido: '',
                                  justificativa: '',
                              },
                          ],
                };
            });
        },
        [alterar],
    );

    const atualizarItem = useCallback<CartValue['atualizarItem']>(
        (tipo, materialId, patch) =>
            alterar(tipo, (c) => ({
                ...c,
                itens: c.itens.map((i) => (i.materialId === materialId ? { ...i, ...patch } : i)),
            })),
        [alterar],
    );

    const remover = useCallback<CartValue['remover']>(
        (tipo, materialId) =>
            alterar(tipo, (c) => ({ ...c, itens: c.itens.filter((i) => i.materialId !== materialId) })),
        [alterar],
    );

    const definir = useCallback<CartValue['definir']>(
        (tipo, patch) => alterar(tipo, (c) => ({ ...c, ...patch })),
        [alterar],
    );

    const limpar = useCallback<CartValue['limpar']>((tipo) => alterar(tipo, () => VAZIO_TIPO), [alterar]);

    const value = useMemo<CartValue>(
        () => ({
            hidratado,
            carrinho: (tipo) => estado[tipo],
            adicionar,
            atualizarItem,
            remover,
            definir,
            limpar,
        }),
        [estado, hidratado, adicionar, atualizarItem, remover, definir, limpar],
    );

    return <CartContext.Provider value={value}>{children}</CartContext.Provider>;
}

export function useCarrinho(): CartValue {
    const ctx = useContext(CartContext);

    if (!ctx) {
        throw new Error('useCarrinho deve ser usado dentro de CartProvider');
    }

    return ctx;
}
