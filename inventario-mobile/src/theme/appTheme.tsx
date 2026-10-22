import { createContext, type ReactNode, useContext, useMemo } from 'react';

export type AppThemeMode = 'light';

export const APP_THEME_COLORS = {
  light: {
    screen: '#F4F7FA',
    surface: '#FFFFFF',
    surfaceMuted: '#F8FAFC',
    surfaceAccent: '#EAF4FB',
    border: '#D9E2EC',
    borderAccent: '#B6D4EA',
    text: '#102A43',
    textMuted: '#627D98',
    textSubtle: '#829AB1',
    primary: '#1E4E79',
    primarySoft: '#EAF4FB',
    primaryText: '#FFFFFF',
    overlay: '#102A4399',
    track: '#D9E2EC',
    input: '#FFFFFF',
    success: '#2F855A',
    successSoft: '#E6F4EA',
    warning: '#B7791F',
    warningSoft: '#FFF4D6',
    info: '#1E4E79',
    infoSoft: '#D9E8F5',
    purple: '#805AD5',
    purpleSoft: '#F0EBFF',
    danger: '#C53030',
    dangerSoft: '#FFF5F5',
    dangerPressed: '#FFE3E3',
  },
};

interface AppThemeContextValue {
  colors: typeof APP_THEME_COLORS.light;
  isDark: boolean;
  mode: AppThemeMode;
}

const AppThemeContext = createContext<AppThemeContextValue | null>(null);

export function AppThemeProvider({ children }: { children: ReactNode }) {
  const value = useMemo<AppThemeContextValue>(() => {
    return {
      colors: APP_THEME_COLORS.light,
      isDark: false,
      mode: 'light',
    };
  }, []);

  return (
    <AppThemeContext.Provider value={value}>
      {children}
    </AppThemeContext.Provider>
  );
}

export function useAppTheme() {
  const context = useContext(AppThemeContext);

  if (!context) {
    throw new Error('useAppTheme must be used inside AppThemeProvider.');
  }

  return context;
}

export function useAppColorScheme(): AppThemeMode {
  return useAppTheme().mode;
}
