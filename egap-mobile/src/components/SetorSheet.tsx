import { Ionicons } from '@expo/vector-icons';
import { useState } from 'react';
import { ActivityIndicator, Modal, Pressable, StyleSheet, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Text } from '@/src/components/Text';
import { ApiError } from '@/src/api/client';
import type { Lotacao } from '@/src/api/types';
import { useSessao } from '@/src/session/SessionProvider';
import { useTheme } from '@/src/theme/ThemeProvider';
import { radius } from '@/src/theme/tokens';

/** Lista de lotações do usuário; ao tocar, troca o setor da sessão. */
export function SetorLista({ onTrocado }: { onTrocado: (setor: Lotacao) => void }) {
    const { colors } = useTheme();
    const { sessao, trocarSetor } = useSessao();
    const [carregandoId, setCarregandoId] = useState<number | null>(null);
    const [erro, setErro] = useState<string | null>(null);

    const escolher = async (lotacao: Lotacao) => {
        if (carregandoId !== null) {
            return;
        }
        if (lotacao.id === sessao?.setor_atual?.id) {
            onTrocado(lotacao);
            return;
        }

        setErro(null);
        setCarregandoId(lotacao.id);
        try {
            await trocarSetor(lotacao.id);
            onTrocado(lotacao);
        } catch (e) {
            setErro(e instanceof ApiError ? e.message : 'Não foi possível trocar o setor.');
        } finally {
            setCarregandoId(null);
        }
    };

    return (
        <View style={styles.lista}>
            {(sessao?.lotacoes ?? []).map((lotacao) => {
                const atual = lotacao.id === sessao?.setor_atual?.id;

                return (
                    <Pressable
                        key={lotacao.id}
                        accessibilityRole="button"
                        accessibilityState={{ selected: atual }}
                        onPress={() => void escolher(lotacao)}
                        style={[
                            styles.item,
                            {
                                backgroundColor: colors.card,
                                borderColor: atual ? colors.primary : colors.border,
                                borderWidth: atual ? 2 : 1,
                            },
                        ]}
                    >
                        <View style={styles.itemTexto}>
                            <Text style={[styles.setor, { color: colors.text }]}>{lotacao.setor}</Text>
                            <Text style={[styles.unidade, { color: colors.textMuted }]}>
                                {lotacao.unidade}
                                {lotacao.principal ? ' · principal' : ''}
                            </Text>
                        </View>
                        {carregandoId === lotacao.id ? (
                            <ActivityIndicator color={colors.primary} />
                        ) : atual ? (
                            <Ionicons name="checkmark-circle" size={22} color={colors.primary} />
                        ) : null}
                    </Pressable>
                );
            })}
            {erro ? <Text style={[styles.erro, { color: colors.danger }]}>{erro}</Text> : null}
        </View>
    );
}

interface SheetProps {
    visible: boolean;
    onClose: () => void;
}

export function SetorSheet({ visible, onClose }: SheetProps) {
    const { colors } = useTheme();
    const insets = useSafeAreaInsets();

    return (
        <Modal visible={visible} transparent animationType="slide" onRequestClose={onClose}>
            <Pressable style={styles.backdrop} onPress={onClose} accessibilityLabel="Fechar" />
            <View style={[styles.sheet, { backgroundColor: colors.background, paddingBottom: insets.bottom + 16 }]}>
                <View style={[styles.handle, { backgroundColor: colors.border }]} />
                <Text style={[styles.titulo, { color: colors.text }]}>Trocar setor</Text>
                <SetorLista onTrocado={onClose} />
            </View>
        </Modal>
    );
}

const styles = StyleSheet.create({
    backdrop: { flex: 1, backgroundColor: 'rgba(15,23,42,0.5)' },
    sheet: {
        borderTopLeftRadius: radius.sheet,
        borderTopRightRadius: radius.sheet,
        paddingHorizontal: 16,
        paddingTop: 10,
        maxHeight: '75%',
    },
    handle: { alignSelf: 'center', width: 40, height: 4, borderRadius: 2, marginBottom: 12 },
    titulo: { fontSize: 18, fontWeight: '800', marginBottom: 12 },
    lista: { gap: 10 },
    item: { flexDirection: 'row', alignItems: 'center', gap: 12, padding: 14, borderRadius: radius.card },
    itemTexto: { flex: 1 },
    setor: { fontSize: 15, fontWeight: '700' },
    unidade: { fontSize: 12, marginTop: 2 },
    erro: { fontSize: 13, marginTop: 4 },
});
