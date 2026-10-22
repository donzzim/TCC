import { createContext, useContext, type ReactNode } from 'react';
import { useColorScheme } from 'react-native';

import { palette, type Palette } from './tokens';

interface ThemeValue {
    colors: Palette;
    dark: boolean;
}

const ThemeContext = createContext<ThemeValue>({ colors: palette.light, dark: false });

export function ThemeProvider({ children }: { children: ReactNode }) {
    const dark = useColorScheme() === 'dark';

    return (
        <ThemeContext.Provider value={{ colors: dark ? palette.dark : palette.light, dark }}>
            {children}
        </ThemeContext.Provider>
    );
}

export function useTheme(): ThemeValue {
    return useContext(ThemeContext);
}
