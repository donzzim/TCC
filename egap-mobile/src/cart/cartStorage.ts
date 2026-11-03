import { apagarJson, gravarJson, lerJson } from '@/src/utils/armazenamentoLocal';

const KEY = 'egap_mobile_carrinho';

export function lerCarrinhoSalvo(): Promise<unknown> {
    return lerJson(KEY);
}

// Toda escrita/limpeza passa por esta fila: nunca há duas operações de storage intercaladas.
let fila: Promise<void> = Promise.resolve();
let timer: ReturnType<typeof setTimeout> | null = null;
const ATRASO = 500;

function enfileirar(op: () => Promise<void>): Promise<void> {
    fila = fila.then(op, op);

    return fila;
}

/** Salva com debounce (junta digitação em uma gravação do estado mais recente). */
export function salvarCarrinho(valor: unknown): void {
    if (timer) clearTimeout(timer);

    timer = setTimeout(() => {
        timer = null;
        void enfileirar(() => gravarJson(KEY, valor));
    }, ATRASO);
}

/** Chamado no logout/401: o carrinho é por aparelho e não pode vazar para o próximo usuário. */
export function limparCarrinhos(): Promise<void> {
    // Cancela gravação pendente: nada pode ser escrito depois da limpeza.
    if (timer) {
        clearTimeout(timer);
        timer = null;
    }

    return enfileirar(() => apagarJson(KEY));
}
