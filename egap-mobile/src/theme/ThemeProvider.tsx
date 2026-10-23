import * as SecureStore from 'expo-secure-store';
import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import { Platform, useColorScheme } from 'react-native';

import { palette, type Palette } from './tokens';

export type TemaPreferencia = 'sistema' | 'claro' | 'escuro';

interface ThemeValue {
    colors: Palette;
    dark: boolean;
    preferencia: TemaPreferencia;
    setPreferencia: (valor: TemaPreferencia) => void;
}

const KEY = 'egap_mobile_tema';

const ThemeContext = createContext<ThemeValue>({
    colors: palette.light,
    dark: false,
    preferencia: 'sistema',
    setPreferencia: () => {},
});

async function lerPreferencia(): Promise<TemaPreferencia | null> {
    try {
        const valor =
            Platform.OS === 'web' ? window.localStorage.getItem(KEY) : await SecureStore.getItemAsync(KEY);

        return valor === 'claro' || valor === 'escuro' || valor === 'sistema' ? valor : null;
    } catch {
        return null;
    }
}

async function salvarPreferencia(valor: TemaPreferencia): Promise<void> {
    try {
        if (Platform.OS === 'web') {
            window.localStorage.setItem(KEY, valor);
        } else {
            await SecureStore.setItemAsync(KEY, valor);
        }
    } catch {
        // sem persistência: vale até fechar o app
    }
}

export function ThemeProvider({ children }: { children: ReactNode }) {
    const sistemaEscuro = useColorScheme() === 'dark';
    const [preferencia, setPreferenciaState] = useState<TemaPreferencia>('sistema');

    useEffect(() => {
        void lerPreferencia().then((valor) => {
            if (valor) {
                setPreferenciaState(valor);
            }
        });
    }, []);

    const setPreferencia = useCallback((valor: TemaPreferencia) => {
        setPreferenciaState(valor);
        void salvarPreferencia(valor);
    }, []);

    const dark = preferencia === 'sistema' ? sistemaEscuro : preferencia === 'escuro';
    const value = useMemo(
        () => ({ colors: dark ? palette.dark : palette.light, dark, preferencia, setPreferencia }),
        [dark, preferencia, setPreferencia],
    );

    return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
}

export function useTheme(): ThemeValue {
    return useContext(ThemeContext);
}
