import { LinearGradient } from 'expo-linear-gradient';
import type { ReactNode } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { gradient, radius } from '@/src/theme/tokens';

interface Props {
    title: string;
    subtitle?: string;
    right?: ReactNode;
    /** Espaço extra embaixo para o cartão flutuante sobrepor o cabeçalho. */
    overlap?: number;
    children?: ReactNode;
}

export function GradientHeader({ title, subtitle, right, overlap = 32, children }: Props) {
    const insets = useSafeAreaInsets();

    return (
        <LinearGradient
            colors={[...gradient]}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
            style={[styles.container, { paddingTop: insets.top + 14, paddingBottom: 16 + overlap }]}
        >
            <View style={styles.row}>
                <View style={styles.texts}>
                    {subtitle ? <Text style={styles.subtitle}>{subtitle}</Text> : null}
                    <Text style={styles.title} numberOfLines={2}>
                        {title}
                    </Text>
                </View>
                {right}
            </View>
            {children}
        </LinearGradient>
    );
}

const styles = StyleSheet.create({
    container: {
        paddingHorizontal: 16,
        borderBottomLeftRadius: radius.sheet,
        borderBottomRightRadius: radius.sheet,
    },
    row: { flexDirection: 'row', alignItems: 'center', gap: 12 },
    texts: { flex: 1 },
    subtitle: { color: 'rgba(255,255,255,0.8)', fontSize: 12, fontWeight: '600' },
    title: { color: '#ffffff', fontSize: 20, fontWeight: '800' },
});
