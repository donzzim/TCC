import * as SecureStore from 'expo-secure-store';
import { Platform } from 'react-native';

const KEY = 'egap_mobile_recentes';
const LIMITE = 10;

export interface Recente {
    patrimonio: string;
    descricao: string;
    /** ISO 8601 */
    quando: string;
}

export async function lerRecentes(): Promise<Recente[]> {
    try {
        const bruto =
            Platform.OS === 'web' ? window.localStorage.getItem(KEY) : await SecureStore.getItemAsync(KEY);
        const lista = bruto ? (JSON.parse(bruto) as Recente[]) : [];

        return Array.isArray(lista) ? lista.slice(0, LIMITE) : [];
    } catch {
        return [];
    }
}

export async function registrarRecente(patrimonio: string, descricao: string | null): Promise<void> {
    try {
        const atuais = await lerRecentes();
        // Descrição truncada: o SecureStore limita o tamanho do valor (cerca de 2 KB).
        const novo: Recente = {
            patrimonio,
            descricao: (descricao ?? '').slice(0, 60),
            quando: new Date().toISOString(),
        };
        const lista = [novo, ...atuais.filter((r) => r.patrimonio !== patrimonio)].slice(0, LIMITE);
        const texto = JSON.stringify(lista);

        if (Platform.OS === 'web') {
            window.localStorage.setItem(KEY, texto);
        } else {
            await SecureStore.setItemAsync(KEY, texto);
        }
    } catch {
        // histórico é só conveniência
    }
}

/** Chamado no logout/401: o histórico é por aparelho e não pode vazar para o próximo usuário. */
export async function limparRecentes(): Promise<void> {
    try {
        if (Platform.OS === 'web') {
            window.localStorage.removeItem(KEY);
        } else {
            await SecureStore.deleteItemAsync(KEY);
        }
    } catch {
        // nada a limpar
    }
}
