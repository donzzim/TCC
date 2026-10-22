import { StyleSheet, Text, View } from 'react-native';

import { useTheme } from '@/src/theme/ThemeProvider';
import { radius } from '@/src/theme/tokens';

interface Props {
    label: string;
    value: string | number;
    tone?: 'primary' | 'success' | 'warning' | 'danger';
}

export function StatPill({ label, value, tone = 'primary' }: Props) {
    const { colors } = useTheme();
    const cor = colors[tone];

    return (
        <View style={[styles.pill, { backgroundColor: `${cor}1f` }]}>
            <Text style={[styles.value, { color: cor }]}>{value}</Text>
            <Text style={[styles.label, { color: colors.textMuted }]}>{label}</Text>
        </View>
    );
}

const styles = StyleSheet.create({
    pill: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 6,
        paddingHorizontal: 12,
        paddingVertical: 6,
        borderRadius: radius.pill,
        alignSelf: 'flex-start',
    },
    value: { fontSize: 14, fontWeight: '800' },
    label: { fontSize: 12, fontWeight: '600' },
});
