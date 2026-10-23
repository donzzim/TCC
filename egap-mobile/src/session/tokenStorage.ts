import * as SecureStore from 'expo-secure-store';
import { Platform } from 'react-native';

const KEY = 'egap_mobile_token';

// expo-secure-store não existe na web: usa localStorage (pode lançar em modo privado).
export async function lerToken(): Promise<string | null> {
    try {
        if (Platform.OS === 'web') {
            return window.localStorage.getItem(KEY);
        }
        return await SecureStore.getItemAsync(KEY);
    } catch {
        return null;
    }
}

export async function salvarToken(token: string): Promise<void> {
    try {
        if (Platform.OS === 'web') {
            window.localStorage.setItem(KEY, token);
            return;
        }
        await SecureStore.setItemAsync(KEY, token);
    } catch {
        // sem persistência: a sessão vale apenas até fechar o app
    }
}

export async function limparToken(): Promise<void> {
    try {
        if (Platform.OS === 'web') {
            window.localStorage.removeItem(KEY);
            return;
        }
        await SecureStore.deleteItemAsync(KEY);
    } catch {
        // nada a limpar
    }
}
