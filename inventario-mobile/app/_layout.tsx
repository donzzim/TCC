import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useState } from 'react';
import { GestureHandlerRootView } from 'react-native-gesture-handler';
import { SafeAreaProvider } from 'react-native-safe-area-context';

import { SessionProvider, useSessao } from '@/src/session/SessionProvider';
import { ThemeProvider } from '@/src/theme/ThemeProvider';

function Rotas() {
    const { status } = useSessao();
    const autenticado = status === 'autenticado';

    return (
        <Stack screenOptions={{ headerShown: false }}>
            {/* Login, carregando e erro de boot são resolvidos dentro de app/index.tsx. */}
            <Stack.Screen name="index" />
            <Stack.Screen name="erro" />
            <Stack.Protected guard={autenticado}>
                <Stack.Screen name="(tabs)" />
                <Stack.Screen name="setor" />
                <Stack.Screen name="perfil" />
                <Stack.Screen name="tutorial" />
                <Stack.Screen name="leitura" options={{ presentation: 'fullScreenModal' }} />
            </Stack.Protected>
        </Stack>
    );
}

export default function RootLayout() {
    const [queryClient] = useState(
        () => new QueryClient({ defaultOptions: { queries: { retry: false, staleTime: 30_000 } } }),
    );

    return (
        <GestureHandlerRootView style={{ flex: 1 }}>
            <SafeAreaProvider>
                <QueryClientProvider client={queryClient}>
                    <SessionProvider>
                        <ThemeProvider>
                            <StatusBar style="light" />
                            <Rotas />
                        </ThemeProvider>
                    </SessionProvider>
                </QueryClientProvider>
            </SafeAreaProvider>
        </GestureHandlerRootView>
    );
}
