import { Ionicons } from '@expo/vector-icons';
import { Image } from 'expo-image';
import { useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { ImagemAmpliada } from '@/src/components/ImagemAmpliada';
import { useTheme } from '@/src/theme/ThemeProvider';

interface Props {
    uri: string | null | undefined;
    /** Nome do material: legenda da foto ampliada e rótulo de acessibilidade. */
    titulo?: string | null;
    tamanho?: number;
    /** Ocupa a largura toda (detalhe do bem); `tamanho` vira a altura. */
    larga?: boolean;
    arredondamento?: number;
}

/** Foto do material; tocar amplia. Sem foto (ou se ela falhar), mostra um ícone no lugar. */
export function Foto({ uri, titulo, tamanho = 48, larga = false, arredondamento = 10 }: Props) {
    const { colors } = useTheme();
    const [ampliada, setAmpliada] = useState(false);
    const [falhou, setFalhou] = useState<string | null>(null);
    const temFoto = !!uri && falhou !== uri;

    const caixa = [
        styles.caixa,
        {
            width: larga ? ('100%' as const) : tamanho,
            height: tamanho,
            borderRadius: arredondamento,
            backgroundColor: colors.background,
            borderColor: colors.border,
        },
    ];

    if (!temFoto) {
        return (
            <View style={caixa} accessibilityLabel="Material sem foto">
                <Ionicons name="image-outline" size={Math.min(28, tamanho * 0.45)} color={colors.textMuted} />
            </View>
        );
    }

    return (
        <>
            <Pressable
                accessibilityRole="imagebutton"
                accessibilityLabel={`Ampliar foto${titulo ? ` de ${titulo}` : ''}`}
                onPress={() => setAmpliada(true)}
                style={caixa}
            >
                <Image
                    source={{ uri }}
                    style={StyleSheet.absoluteFill}
                    contentFit={larga ? 'contain' : 'cover'}
                    transition={150}
                    onError={() => setFalhou(uri)}
                />
            </Pressable>
            {ampliada ? <ImagemAmpliada uri={uri} titulo={titulo} onClose={() => setAmpliada(false)} /> : null}
        </>
    );
}

const styles = StyleSheet.create({
    caixa: {
        overflow: 'hidden',
        alignItems: 'center',
        justifyContent: 'center',
        borderWidth: StyleSheet.hairlineWidth,
    },
});
