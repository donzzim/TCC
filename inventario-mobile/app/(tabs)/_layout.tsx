import { Ionicons } from '@expo/vector-icons';
import type { BottomTabBarProps } from '@react-navigation/bottom-tabs';
import { LinearGradient } from 'expo-linear-gradient';
import { Tabs, useRouter } from 'expo-router';
import { Pressable, StyleSheet, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Text } from '@/src/components/Text';
import { useTheme } from '@/src/theme/ThemeProvider';
import { gradient } from '@/src/theme/tokens';

type IconName = React.ComponentProps<typeof Ionicons>['name'];

const ABAS: Record<string, { titulo: string; icone: IconName; ativo: IconName }> = {
    index: { titulo: 'Início', icone: 'home-outline', ativo: 'home' },
    bens: { titulo: 'Bens', icone: 'cube-outline', ativo: 'cube' },
    conferencia: { titulo: 'Conferência', icone: 'checkmark-done-outline', ativo: 'checkmark-done' },
    pedidos: { titulo: 'Pedidos', icone: 'cart-outline', ativo: 'cart' },
};

const ALTURA_BARRA = 58;
const BOTAO = 56;
const SALIENCIA = 24;

function TabBar({ state, navigation }: BottomTabBarProps) {
    const { colors } = useTheme();
    const router = useRouter();
    const insets = useSafeAreaInsets();
    const abaAtual = state.routes[state.index]?.name;

    const abrirLeitura = () =>
        router.push({
            pathname: '/leitura',
            params: { modo: abaAtual === 'conferencia' ? 'conferencia' : 'consulta' },
        });

    const itens = state.routes.filter((r) => ABAS[r.name]);
    const renderAba = (rota: (typeof itens)[number]) => {
        const info = ABAS[rota.name];
        const focada = state.routes[state.index]?.key === rota.key;
        const cor = focada ? colors.primary : colors.textMuted;

        return (
            <Pressable
                key={rota.key}
                accessibilityRole="tab"
                accessibilityState={{ selected: focada }}
                accessibilityLabel={info.titulo}
                onPress={() => {
                    const evento = navigation.emit({ type: 'tabPress', target: rota.key, canPreventDefault: true });

                    if (!focada && !evento.defaultPrevented) {
                        navigation.navigate(rota.name, rota.params);
                    }
                }}
                style={styles.aba}
            >
                <Ionicons name={focada ? info.ativo : info.icone} size={22} color={cor} />
                <Text style={[styles.abaTexto, { color: cor }]} numberOfLines={1}>
                    {info.titulo}
                </Text>
            </Pressable>
        );
    };

    return (
        <View style={{ height: SALIENCIA + ALTURA_BARRA + insets.bottom }}>
            <View
                style={[
                    styles.fundo,
                    {
                        top: SALIENCIA,
                        backgroundColor: colors.card,
                        borderTopColor: colors.border,
                    },
                ]}
            />
            <View style={[styles.linha, { top: SALIENCIA, height: ALTURA_BARRA }]}>
                {itens.slice(0, 2).map(renderAba)}
                <View style={styles.aba} />
                {itens.slice(2).map(renderAba)}
            </View>
            <Pressable
                accessibilityRole="button"
                accessibilityLabel="Ler plaqueta"
                onPress={abrirLeitura}
                style={styles.botaoArea}
            >
                <LinearGradient
                    colors={[gradient[1], gradient[2]]}
                    start={{ x: 0, y: 0 }}
                    end={{ x: 1, y: 1 }}
                    style={styles.botao}
                >
                    <Ionicons name="scan-outline" size={26} color="#ffffff" />
                </LinearGradient>
            </Pressable>
        </View>
    );
}

export default function TabsLayout() {
    return (
        <Tabs screenOptions={{ headerShown: false }} tabBar={(props) => <TabBar {...props} />}>
            <Tabs.Screen name="index" options={{ title: 'Início' }} />
            <Tabs.Screen name="bens" options={{ title: 'Bens' }} />
            <Tabs.Screen name="conferencia" options={{ title: 'Conferência' }} />
            <Tabs.Screen name="pedidos" options={{ title: 'Pedidos' }} />
        </Tabs>
    );
}

const styles = StyleSheet.create({
    fundo: {
        position: 'absolute',
        left: 0,
        right: 0,
        bottom: 0,
        borderTopWidth: StyleSheet.hairlineWidth,
        shadowColor: '#000000',
        shadowOpacity: 0.06,
        shadowRadius: 14,
        shadowOffset: { width: 0, height: -4 },
        elevation: 8,
    },
    linha: { position: 'absolute', left: 0, right: 0, flexDirection: 'row', alignItems: 'center' },
    aba: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: 3, height: '100%' },
    abaTexto: { fontSize: 10, fontWeight: '600' },
    // Dentro dos limites da barra para o toque funcionar também no Android.
    botaoArea: {
        position: 'absolute',
        top: 0,
        alignSelf: 'center',
        width: BOTAO + 12,
        height: BOTAO + 12,
        alignItems: 'center',
        justifyContent: 'center',
    },
    botao: {
        width: BOTAO,
        height: BOTAO,
        borderRadius: BOTAO / 2,
        alignItems: 'center',
        justifyContent: 'center',
        shadowColor: '#2563eb',
        shadowOpacity: 0.4,
        shadowRadius: 14,
        shadowOffset: { width: 0, height: 6 },
        elevation: 8,
    },
});
