import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { Pressable, StyleSheet, View } from 'react-native';

import { Text } from '@/src/components/Text';
import { useNaoLidas } from '@/src/hooks/useNotificacoes';
import { useTheme } from '@/src/theme/ThemeProvider';

/** Sino do cabeçalho: abre a lista de notificações e mostra quantas não foram lidas. */
export function SinoNotificacoes() {
    const { colors } = useTheme();
    const router = useRouter();
    const { data: naoLidas = 0 } = useNaoLidas();
    const rotulo = naoLidas > 9 ? '9+' : String(naoLidas);

    return (
        <Pressable
            accessibilityRole="button"
            accessibilityLabel={naoLidas > 0 ? `Notificações, ${naoLidas} não lidas` : 'Notificações'}
            onPress={() => router.push('/notificacoes')}
            hitSlop={6}
            style={styles.botao}
        >
            <Ionicons name="notifications-outline" size={20} color="#ffffff" />
            {naoLidas > 0 ? (
                <View style={[styles.bolinha, { backgroundColor: colors.danger }]}>
                    <Text style={styles.numero}>{rotulo}</Text>
                </View>
            ) : null}
        </Pressable>
    );
}

const styles = StyleSheet.create({
    botao: {
        width: 36,
        height: 36,
        borderRadius: 18,
        backgroundColor: 'rgba(255,255,255,0.2)',
        alignItems: 'center',
        justifyContent: 'center',
    },
    bolinha: {
        position: 'absolute',
        top: -3,
        right: -3,
        minWidth: 18,
        height: 18,
        borderRadius: 9,
        paddingHorizontal: 4,
        alignItems: 'center',
        justifyContent: 'center',
        borderWidth: 1.5,
        borderColor: '#ffffff',
    },
    numero: { color: '#ffffff', fontSize: 10, fontWeight: '800', lineHeight: 13 },
});
