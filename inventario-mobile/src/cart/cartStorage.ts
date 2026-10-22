import * as SecureStore from 'expo-secure-store';
import { Platform } from 'react-native';

const KEY = 'egap_mobile_carrinho';
// SecureStore limita cada valor a cerca de 2 KB: o carrinho é gravado em pedaços.
const PEDACO = 1500;

export async function lerCarrinhoSalvo(): Promise<unknown> {
    try {
        if (Platform.OS === 'web') {
            const bruto = window.localStorage.getItem(KEY);

            return bruto ? JSON.parse(bruto) : null;
        }

        const total = Number(await SecureStore.getItemAsync(`${KEY}_n`));

        if (!total) return null;

        let texto = '';

        for (let i = 0; i < total; i += 1) {
            const parte = await SecureStore.getItemAsync(`${KEY}_${i}`);

            if (parte === null) return null;

            texto += parte;
        }

        return JSON.parse(texto);
    } catch {
        return null;
    }
}

export async function salvarCarrinho(valor: unknown): Promise<void> {
    try {
        const texto = JSON.stringify(valor);

        if (Platform.OS === 'web') {
            window.localStorage.setItem(KEY, texto);
            return;
        }

        const anterior = Number(await SecureStore.getItemAsync(`${KEY}_n`)) || 0;
        const partes = Math.ceil(texto.length / PEDACO);

        for (let i = 0; i < partes; i += 1) {
            await SecureStore.setItemAsync(`${KEY}_${i}`, texto.slice(i * PEDACO, (i + 1) * PEDACO));
        }
        await SecureStore.setItemAsync(`${KEY}_n`, String(partes));

        for (let i = partes; i < anterior; i += 1) {
            await SecureStore.deleteItemAsync(`${KEY}_${i}`);
        }
    } catch {
        // o carrinho segue em memória
    }
}

/** Chamado no logout/401: o carrinho é por aparelho e não pode vazar para o próximo usuário. */
export async function limparCarrinhos(): Promise<void> {
    try {
        if (Platform.OS === 'web') {
            window.localStorage.removeItem(KEY);
            return;
        }

        const total = Number(await SecureStore.getItemAsync(`${KEY}_n`)) || 0;

        for (let i = 0; i < total; i += 1) {
            await SecureStore.deleteItemAsync(`${KEY}_${i}`);
        }
        await SecureStore.deleteItemAsync(`${KEY}_n`);
    } catch {
        // nada a limpar
    }
}
