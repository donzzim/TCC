import { Ionicons } from '@expo/vector-icons';
import { useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { SetorSheet } from '@/src/components/SetorSheet';
import { Text } from '@/src/components/Text';
import { useSessao } from '@/src/session/SessionProvider';
import { radius } from '@/src/theme/tokens';

export function podeTrocarSetor(sessao: ReturnType<typeof useSessao>['sessao']): boolean {
    return !sessao?.lotacao_legada && (sessao?.lotacoes.length ?? 0) > 1;
}

export function SetorSeletor() {
    const { sessao } = useSessao();
    const [aberto, setAberto] = useState(false);
    const setor = sessao?.setor_atual;
    const trocavel = podeTrocarSetor(sessao);
    const rotulo = setor ? `${setor.setor} · ${setor.unidade}` : 'Sem setor';

    if (!trocavel) {
        return (
            <View style={styles.pill} accessibilityLabel={`Setor: ${rotulo}`}>
                <Ionicons name="business-outline" size={14} color="#ffffff" />
                <Text style={styles.texto} numberOfLines={1}>
                    {rotulo}
                </Text>
            </View>
        );
    }

    return (
        <>
            <Pressable
                accessibilityRole="button"
                accessibilityLabel={`Trocar setor. Atual: ${rotulo}`}
                onPress={() => setAberto(true)}
                hitSlop={6}
                style={({ pressed }) => [styles.pill, pressed && styles.pressionado]}
            >
                <Ionicons name="business-outline" size={14} color="#ffffff" />
                <Text style={styles.texto} numberOfLines={1}>
                    {rotulo}
                </Text>
                <Ionicons name="chevron-down" size={14} color="#ffffff" />
            </Pressable>
            <SetorSheet visible={aberto} onClose={() => setAberto(false)} />
        </>
    );
}

const styles = StyleSheet.create({
    pill: {
        marginTop: 10,
        alignSelf: 'flex-start',
        flexDirection: 'row',
        alignItems: 'center',
        gap: 6,
        backgroundColor: 'rgba(255,255,255,0.18)',
        borderRadius: radius.pill,
        paddingHorizontal: 12,
        paddingVertical: 6,
        maxWidth: '100%',
    },
    pressionado: { backgroundColor: 'rgba(255,255,255,0.28)' },
    texto: { color: '#ffffff', fontSize: 12, fontWeight: '600', flexShrink: 1 },
});
