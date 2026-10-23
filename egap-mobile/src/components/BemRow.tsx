import { Pressable, StyleSheet, View } from 'react-native';

import { Foto } from '@/src/components/Foto';
import { Text } from '@/src/components/Text';
import type { Bem } from '@/src/api/types';
import { useTheme } from '@/src/theme/ThemeProvider';
import { radius } from '@/src/theme/tokens';

interface Props {
    bem: Pick<Bem, 'patrimonio' | 'descricao' | 'descricao_resumida' | 'situacao' | 'imagem_url'>;
    onPress: () => void;
}

export function BemRow({ bem, onPress }: Props) {
    const { colors } = useTheme();
    const titulo = bem.descricao_resumida || bem.descricao || 'Sem descrição';

    return (
        <Pressable
            accessibilityRole="button"
            onPress={onPress}
            style={[styles.row, { backgroundColor: colors.card, borderColor: colors.border }]}
        >
            <Foto uri={bem.imagem_url} titulo={titulo} />
            <View style={styles.texts}>
                <Text style={[styles.titulo, { color: colors.text }]} numberOfLines={2}>
                    {titulo}
                </Text>
                <Text style={[styles.sub, { color: colors.textMuted }]}>Nº {bem.patrimonio ?? '-'}</Text>
            </View>
            {bem.situacao ? (
                <View style={[styles.pill, { backgroundColor: `${colors.primary}1f` }]}>
                    <Text style={[styles.pillTexto, { color: colors.primary }]} numberOfLines={1}>
                        {bem.situacao}
                    </Text>
                </View>
            ) : null}
        </Pressable>
    );
}

const styles = StyleSheet.create({
    row: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 10,
        padding: 12,
        borderRadius: 14,
        borderWidth: StyleSheet.hairlineWidth,
    },
    texts: { flex: 1 },
    titulo: { fontSize: 14, fontWeight: '600' },
    sub: { fontSize: 12, marginTop: 2 },
    pill: { paddingHorizontal: 10, paddingVertical: 3, borderRadius: radius.pill, maxWidth: 130 },
    pillTexto: { fontSize: 11, fontWeight: '700' },
});
