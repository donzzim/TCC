import { StyleSheet, View } from 'react-native';
import Svg, { Circle } from 'react-native-svg';

import { Text } from '@/src/components/Text';
import { useTheme } from '@/src/theme/ThemeProvider';

interface Props {
    percent: number;
    size?: number;
}

export function ProgressRing({ percent, size = 62 }: Props) {
    const { colors } = useTheme();
    const valor = Math.max(0, Math.min(100, Number.isFinite(percent) ? percent : 0));
    const stroke = Math.max(6, size * 0.14);
    const r = (size - stroke) / 2;
    const c = 2 * Math.PI * r;

    return (
        <View style={{ width: size, height: size }}>
            <Svg width={size} height={size} style={styles.svg}>
                <Circle cx={size / 2} cy={size / 2} r={r} stroke={colors.track} strokeWidth={stroke} fill="none" />
                <Circle
                    cx={size / 2}
                    cy={size / 2}
                    r={r}
                    stroke={colors.primary}
                    strokeWidth={stroke}
                    fill="none"
                    strokeLinecap="round"
                    strokeDasharray={`${c} ${c}`}
                    strokeDashoffset={c * (1 - valor / 100)}
                    rotation={-90}
                    origin={`${size / 2}, ${size / 2}`}
                />
            </Svg>
            <View style={styles.center}>
                <Text style={[styles.label, { color: colors.text, fontSize: size * 0.21 }]}>
                    {Math.round(valor)}%
                </Text>
            </View>
        </View>
    );
}

const styles = StyleSheet.create({
    svg: { position: 'absolute' },
    center: { ...StyleSheet.absoluteFillObject, alignItems: 'center', justifyContent: 'center' },
    label: { fontWeight: '800' },
});
