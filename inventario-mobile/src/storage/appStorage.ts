import * as SecureStore from 'expo-secure-store';
import { Platform } from 'react-native';

const memoryStorage = new Map<string, string>();

function getWebStorage(): Storage | null {
    if (typeof window === 'undefined' || !window.localStorage) {
        return null;
    }

    return window.localStorage;
}

export const appStorage = {
    async getItem(key: string): Promise<string | null> {
        if (Platform.OS === 'web') {
            return getWebStorage()?.getItem(key) ?? memoryStorage.get(key) ?? null;
        }

        return SecureStore.getItemAsync(key);
    },

    async setItem(key: string, value: string): Promise<void> {
        if (Platform.OS === 'web') {
            const webStorage = getWebStorage();

            if (webStorage) {
                webStorage.setItem(key, value);
            } else {
                memoryStorage.set(key, value);
            }

            return;
        }

        await SecureStore.setItemAsync(key, value);
    },

    async deleteItem(key: string): Promise<void> {
        if (Platform.OS === 'web') {
            getWebStorage()?.removeItem(key);
            memoryStorage.delete(key);
            return;
        }

        await SecureStore.deleteItemAsync(key);
    },
};
