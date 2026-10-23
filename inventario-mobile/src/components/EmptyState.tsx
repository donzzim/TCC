import { Pressable, StyleSheet, View } from 'react-native';

import { Text } from '@/src/components/Text';
import { useTheme } from '@/src/theme/ThemeProvider';
import { radius } from '@/src/theme/tokens';

interface Props {
    title: string;
    message?: string;
    actionLabel?: string;
    onAction?: () => void;
}

export function EmptyState({ title, message, actionLabel, onAction }: Props) {
    const { colors } = useTheme();

    return (
        <View style={styles.container}>
            <Text style={[styles.title, { color: colors.text }]}>{title}</Text>
            {message ? <Text style={[styles.message, { color: colors.textMuted }]}>{message}</Text> : null}
            {actionLabel && onAction ? (
                <Pressable
                    accessibilityRole="button"
                    onPress={onAction}
                    style={[styles.button, { backgroundColor: colors.primary }]}
                >
                    <Text style={[styles.buttonText, { color: colors.onPrimary }]}>{actionLabel}</Text>
                </Pressable>
            ) : null}
        </View>
    );
}

const styles = StyleSheet.create({
    container: { alignItems: 'center', padding: 32, gap: 8 },
    title: { fontSize: 16, fontWeight: '700', textAlign: 'center' },
    message: { fontSize: 14, textAlign: 'center' },
    button: { marginTop: 8, paddingHorizontal: 20, paddingVertical: 10, borderRadius: radius.pill },
    buttonText: { fontWeight: '700' },
});
