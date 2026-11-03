import * as SecureStore from 'expo-secure-store';
import { Platform } from 'react-native';

/**
 * JSON guardado no aparelho: localStorage na web, SecureStore no celular.
 * O SecureStore limita cada valor a cerca de 2 KB, então o texto é gravado em pedaços
 * (`<chave>_0`, `<chave>_1`... e o total em `<chave>_n`).
 */
const PEDACO = 1500;

export async function lerJson(chave: string): Promise<unknown> {
    try {
        if (Platform.OS === 'web') {
            const bruto = window.localStorage.getItem(chave);

            return bruto ? JSON.parse(bruto) : null;
        }

        const total = Number(await SecureStore.getItemAsync(`${chave}_n`));

        if (!total) return null;

        let texto = '';

        for (let i = 0; i < total; i += 1) {
            const parte = await SecureStore.getItemAsync(`${chave}_${i}`);

            if (parte === null) return null;

            texto += parte;
        }

        return JSON.parse(texto);
    } catch {
        return null;
    }
}

/** Falha em silêncio: quem chama continua com o valor em memória. */
export async function gravarJson(chave: string, valor: unknown): Promise<void> {
    try {
        const texto = JSON.stringify(valor);

        if (Platform.OS === 'web') {
            window.localStorage.setItem(chave, texto);
            return;
        }

        const anterior = Number(await SecureStore.getItemAsync(`${chave}_n`)) || 0;
        const partes = Math.ceil(texto.length / PEDACO);

        for (let i = 0; i < partes; i += 1) {
            await SecureStore.setItemAsync(`${chave}_${i}`, texto.slice(i * PEDACO, (i + 1) * PEDACO));
        }
        await SecureStore.setItemAsync(`${chave}_n`, String(partes));

        for (let i = partes; i < anterior; i += 1) {
            await SecureStore.deleteItemAsync(`${chave}_${i}`);
        }
    } catch {
        // segue em memória
    }
}

export async function apagarJson(chave: string): Promise<void> {
    try {
        if (Platform.OS === 'web') {
            window.localStorage.removeItem(chave);
            return;
        }

        const total = Number(await SecureStore.getItemAsync(`${chave}_n`)) || 0;

        for (let i = 0; i < total; i += 1) {
            await SecureStore.deleteItemAsync(`${chave}_${i}`);
        }
        await SecureStore.deleteItemAsync(`${chave}_n`);
    } catch {
        // nada a limpar
    }
}
