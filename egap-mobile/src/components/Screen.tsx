import type { ReactNode } from 'react';
import { ScrollView, StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { useTheme } from '@/src/theme/ThemeProvider';

interface Props {
    children: ReactNode;
    scroll?: boolean;
    /** Aplica o inset superior (desligue quando houver GradientHeader). */
    safeTop?: boolean;
    contentStyle?: StyleProp<ViewStyle>;
}

export function Screen({ children, scroll = true, safeTop = true, contentStyle }: Props) {
    const { colors } = useTheme();
    const insets = useSafeAreaInsets();
    const paddingTop = safeTop ? insets.top : 0;

    if (!scroll) {
        return (
            <View style={[styles.flex, { backgroundColor: colors.background, paddingTop }, contentStyle]}>
                {children}
            </View>
        );
    }

    return (
        <ScrollView
            style={[styles.flex, { backgroundColor: colors.background }]}
            contentContainerStyle={[{ paddingTop, paddingBottom: 24 }, contentStyle]}
            keyboardShouldPersistTaps="handled"
        >
            {children}
        </ScrollView>
    );
}

const styles = StyleSheet.create({ flex: { flex: 1 } });
