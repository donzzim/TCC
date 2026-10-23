import { Ionicons } from '@expo/vector-icons';
import { usePathname, useRouter } from 'expo-router';
import { useEffect, useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Text } from '@/src/components/Text';
import { useNotificacoes } from '@/src/hooks/useNotificacoes';
import { useTheme } from '@/src/theme/ThemeProvider';
import { radius } from '@/src/theme/tokens';

const DURACAO = 8_000;
const ABAS = new Set(['/', '/bens', '/conferencia', '/pedidos']);

// Só em memória: o aviso aparece uma vez por abertura do app (não é persistido).
let jaExibido = false;

type Fase = 'aguardando' | 'visivel' | 'encerrado';

/** Aviso de notificações não lidas, montado no layout das abas (não cobre login nem leitura). */
export function ToastNotificacoes() {
    const { colors } = useTheme();
    const router = useRouter();
    const pathname = usePathname();
    const insets = useSafeAreaInsets();
    const { data } = useNotificacoes();
    const [fase, setFase] = useState<Fase>(() => (jaExibido ? 'encerrado' : 'aguardando'));

    const primeira = data?.pages[0];
    const naoLidas = primeira?.meta.nao_lidas ?? 0;

    // Decide na primeira resposta da API (ajuste durante a renderização, sem efeito).
    if (fase === 'aguardando' && primeira) {
        setFase(naoLidas > 0 ? 'visivel' : 'encerrado');
    }

    useEffect(() => {
        if (fase !== 'aguardando') jaExibido = true;
    }, [fase]);

    useEffect(() => {
        if (fase !== 'visivel') return;

        const timer = setTimeout(() => setFase('encerrado'), DURACAO);

        return () => clearTimeout(timer);
    }, [fase]);

    if (fase !== 'visivel' || naoLidas === 0 || !ABAS.has(pathname)) return null;

    const titulos = (primeira?.notificacoes ?? []).filter((n) => !n.lida).slice(0, 3);
    const fechar = () => setFase('encerrado');

    return (
        <View pointerEvents="box-none" style={[styles.camada, { top: insets.top + 8 }]}>
            <View
                accessibilityRole="alert"
                style={[styles.toast, { backgroundColor: colors.card, borderColor: colors.border }]}
            >
                <View style={styles.cabecalho}>
                    <View style={[styles.icone, { backgroundColor: `${colors.primary}1f` }]}>
                        <Ionicons name="notifications" size={18} color={colors.primary} />
                    </View>
                    <Text style={[styles.titulo, { color: colors.text }]}>
                        {naoLidas === 1 ? '1 notificação não lida' : `${naoLidas} notificações não lidas`}
                    </Text>
                    <Pressable accessibilityRole="button" accessibilityLabel="Fechar" onPress={fechar} hitSlop={10}>
                        <Ionicons name="close" size={20} color={colors.textMuted} />
                    </Pressable>
                </View>
                {titulos.map((n) => (
                    <Text key={n.id} style={[styles.item, { color: colors.textMuted }]} numberOfLines={1}>
                        • {n.titulo}
                    </Text>
                ))}
                <Pressable
                    accessibilityRole="button"
                    onPress={() => {
                        fechar();
                        router.push('/notificacoes');
                    }}
                    style={[styles.botao, { backgroundColor: colors.primary }]}
                >
                    <Text style={[styles.botaoTexto, { color: colors.onPrimary }]}>Ver todas</Text>
                </Pressable>
            </View>
        </View>
    );
}

const styles = StyleSheet.create({
    camada: { position: 'absolute', left: 12, right: 12, alignItems: 'center' },
    toast: {
        width: '100%',
        maxWidth: 480,
        padding: 14,
        gap: 6,
        borderRadius: radius.card,
        borderWidth: StyleSheet.hairlineWidth,
        shadowColor: '#000000',
        shadowOpacity: 0.18,
        shadowRadius: 18,
        shadowOffset: { width: 0, height: 8 },
        elevation: 12,
    },
    cabecalho: { flexDirection: 'row', alignItems: 'center', gap: 10, marginBottom: 2 },
    icone: { width: 32, height: 32, borderRadius: 16, alignItems: 'center', justifyContent: 'center' },
    titulo: { flex: 1, fontSize: 14, fontWeight: '800' },
    item: { fontSize: 13 },
    botao: {
        alignSelf: 'flex-end',
        marginTop: 4,
        paddingHorizontal: 16,
        paddingVertical: 8,
        borderRadius: radius.pill,
    },
    botaoTexto: { fontSize: 13, fontWeight: '700' },
});
