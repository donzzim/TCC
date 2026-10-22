import { DefaultTheme, ThemeProvider } from '@react-navigation/native';
import { router, Stack, type Href } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useEffect, useRef } from 'react';
import { StyleSheet } from 'react-native';
import { GestureHandlerRootView } from 'react-native-gesture-handler';
import 'react-native-reanimated';

import { subscribeAppError, type AppErrorEvent } from '@/src/errors/appErrorEvents';
import { AppThemeProvider } from '@/src/theme/appTheme';

function buildErrorHref(event: AppErrorEvent): Href {
  const params = new URLSearchParams();

  params.set('kind', event.kind);
  params.set('title', event.title);
  params.set('message', event.message);

  if (event.status !== undefined) {
    params.set('status', String(event.status));
  }

  return `/erro?${params.toString()}` as Href;
}

function RootNavigator() {
  const lastErrorRef = useRef<{ signature: string; notifiedAt: number } | null>(null);

  useEffect(() => {
    return subscribeAppError((event) => {
      const signature = `${event.kind}:${event.status ?? 'no-status'}:${event.message}`;
      const now = Date.now();

      if (
        lastErrorRef.current?.signature === signature
        && now - lastErrorRef.current.notifiedAt < 800
      ) {
        return;
      }

      lastErrorRef.current = { signature, notifiedAt: now };
      router.push(buildErrorHref(event));
    });
  }, []);

  return (
    <ThemeProvider value={DefaultTheme}>
      <Stack>
        <Stack.Screen name="index" options={{ headerShown: false }} />
        <Stack.Screen name="erro" options={{ headerShown: false }} />
        <Stack.Screen name="patrimonio" options={{ headerShown: false }} />
        <Stack.Screen name="pedidos" options={{ headerShown: false }} />
        <Stack.Screen name="configuracoes" options={{ headerShown: false }} />
      </Stack>
      <StatusBar style="dark" />
    </ThemeProvider>
  );
}

export default function RootLayout() {
  return (
    <GestureHandlerRootView style={styles.root}>
      <AppThemeProvider>
        <RootNavigator />
      </AppThemeProvider>
    </GestureHandlerRootView>
  );
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
  },
});
