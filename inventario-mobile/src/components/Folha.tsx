import type { ReactNode } from 'react';
import { KeyboardAvoidingView, Modal, Platform, Pressable, ScrollView, StyleSheet } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Text } from '@/src/components/Text';
import { useTheme } from '@/src/theme/ThemeProvider';
import { radius } from '@/src/theme/tokens';

interface Props {
    visible: boolean;
    title: string;
    onClose: () => void;
    children: ReactNode;
}

/** Folha inferior modal (formulários e confirmações curtas). */
export function Folha({ visible, title, onClose, children }: Props) {
    const { colors } = useTheme();
    const insets = useSafeAreaInsets();

    return (
        <Modal visible={visible} transparent animationType="slide" onRequestClose={onClose}>
            <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
                <Pressable accessibilityLabel="Fechar" style={styles.fundo} onPress={onClose} />
                <ScrollView
                    style={[styles.folha, { backgroundColor: colors.background }]}
                    contentContainerStyle={{ padding: 16, paddingBottom: insets.bottom + 16, gap: 12 }}
                    keyboardShouldPersistTaps="handled"
                >
                    <Text style={[styles.titulo, { color: colors.text }]}>{title}</Text>
                    {children}
                </ScrollView>
            </KeyboardAvoidingView>
        </Modal>
    );
}

const styles = StyleSheet.create({
    flex: { flex: 1, justifyContent: 'flex-end' },
    fundo: { ...StyleSheet.absoluteFillObject, backgroundColor: 'rgba(15,23,42,0.5)' },
    folha: { maxHeight: '85%', flexGrow: 0, borderTopLeftRadius: radius.sheet, borderTopRightRadius: radius.sheet },
    titulo: { fontSize: 18, fontWeight: '800' },
});
