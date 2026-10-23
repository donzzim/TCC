import { useFonts } from 'expo-font';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { DarkTheme, DefaultTheme, SplashScreen, Stack, ThemeProvider as NavegacaoThemeProvider } from 'expo-router';
import type { Theme } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useEffect, useMemo, useState } from 'react';
import { GestureHandlerRootView } from 'react-native-gesture-handler';
import { SafeAreaProvider } from 'react-native-safe-area-context';

import { CartProvider } from '@/src/cart/CartProvider';
import { SessionProvider, useSessao } from '@/src/session/SessionProvider';
import { fontes } from '@/src/theme/fonts';
import { ThemeProvider, useTheme } from '@/src/theme/ThemeProvider';

SplashScreen.preventAutoHideAsync();

function Rotas() {
    const { status } = useSessao();
    const autenticado = status === 'autenticado';
    const { colors, dark } = useTheme();
    // Sem isso a navegação fica no tema claro padrão e o fundo dela aparece
    // no tema escuro (faixa acima da barra de abas e transições).
    const temaNavegacao = useMemo<Theme>(() => {
        const base = dark ? DarkTheme : DefaultTheme;

        return {
            ...base,
            colors: {
                ...base.colors,
                primary: colors.primary,
                background: colors.background,
                card: colors.card,
                text: colors.text,
                border: colors.border,
            },
        };
    }, [colors, dark]);

    return (
        <NavegacaoThemeProvider value={temaNavegacao}>
            <Stack screenOptions={{ headerShown: false }}>
                {/* Login, carregando e erro de boot são resolvidos dentro de app/index.tsx. */}
                <Stack.Screen name="index" />
                <Stack.Screen name="erro" />
                <Stack.Protected guard={autenticado}>
                    <Stack.Screen name="(tabs)" />
                    <Stack.Screen name="setor" />
                    <Stack.Screen name="perfil" />
                    <Stack.Screen name="tutorial" />
                    <Stack.Screen name="notificacoes" />
                    <Stack.Screen name="bem/[patrimonio]" />
                    <Stack.Screen name="pedidos/novo" />
                    <Stack.Screen name="pedidos/carrinho" />
                    <Stack.Screen name="leitura" options={{ presentation: 'fullScreenModal' }} />
                </Stack.Protected>
            </Stack>
        </NavegacaoThemeProvider>
    );
}

export default function RootLayout() {
    const [queryClient] = useState(
        () => new QueryClient({ defaultOptions: { queries: { retry: false, staleTime: 30_000 } } }),
    );
    // Em caso de erro no carregamento, segue com a fonte do sistema em vez de travar na splash.
    const [fontesProntas, erroFontes] = useFonts(fontes);

    useEffect(() => {
        if (fontesProntas || erroFontes) SplashScreen.hideAsync();
    }, [fontesProntas, erroFontes]);

    if (!fontesProntas && !erroFontes) return null;

    return (
        <GestureHandlerRootView style={{ flex: 1 }}>
            <SafeAreaProvider>
                <QueryClientProvider client={queryClient}>
                    <SessionProvider>
                        <CartProvider>
                            <ThemeProvider>
                                <StatusBar style="light" />
                                <Rotas />
                            </ThemeProvider>
                        </CartProvider>
                    </SessionProvider>
                </QueryClientProvider>
            </SafeAreaProvider>
        </GestureHandlerRootView>
    );
}
