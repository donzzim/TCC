import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useState } from 'react';
import { SafeAreaProvider } from 'react-native-safe-area-context';

import { SessionProvider } from '@/src/session/SessionProvider';
import { ThemeProvider } from '@/src/theme/ThemeProvider';

export default function RootLayout() {
    const [queryClient] = useState(
        () => new QueryClient({ defaultOptions: { queries: { retry: false, staleTime: 30_000 } } }),
    );

    return (
        <SafeAreaProvider>
            <QueryClientProvider client={queryClient}>
                <SessionProvider>
                    <ThemeProvider>
                        <StatusBar style="light" />
                        <Stack screenOptions={{ headerShown: false }} />
                    </ThemeProvider>
                </SessionProvider>
            </QueryClientProvider>
        </SafeAreaProvider>
    );
}
