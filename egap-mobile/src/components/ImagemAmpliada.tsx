import { Ionicons } from '@expo/vector-icons';
import { Image } from 'expo-image';
import { Modal, Pressable, StyleSheet, View } from 'react-native';
import { Gesture, GestureDetector, GestureHandlerRootView } from 'react-native-gesture-handler';
import Animated, { useAnimatedStyle, useSharedValue, withTiming } from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Text } from '@/src/components/Text';

const ZOOM_MAX = 5;
const ZOOM_DUPLO = 2.5;

interface Props {
    uri: string;
    titulo?: string | null;
    onClose: () => void;
}

/** Foto em tela cheia: pinça amplia, arrastar move e toque duplo alterna o zoom. */
export function ImagemAmpliada({ uri, titulo, onClose }: Props) {
    const insets = useSafeAreaInsets();
    const escala = useSharedValue(1);
    const escalaInicial = useSharedValue(1);
    const x = useSharedValue(0);
    const y = useSharedValue(0);
    const xInicial = useSharedValue(0);
    const yInicial = useSharedValue(0);

    const voltar = () => {
        'worklet';
        escala.value = withTiming(1);
        x.value = withTiming(0);
        y.value = withTiming(0);
    };

    const pinca = Gesture.Pinch()
        .onStart(() => {
            escalaInicial.value = escala.value;
        })
        .onUpdate((e) => {
            escala.value = Math.min(ZOOM_MAX, Math.max(1, escalaInicial.value * e.scale));
        })
        .onEnd(() => {
            if (escala.value <= 1.01) voltar();
        });

    const arraste = Gesture.Pan()
        .averageTouches(true)
        .onStart(() => {
            xInicial.value = x.value;
            yInicial.value = y.value;
        })
        .onUpdate((e) => {
            if (escala.value > 1) {
                x.value = xInicial.value + e.translationX;
                y.value = yInicial.value + e.translationY;
            }
        });

    const toqueDuplo = Gesture.Tap()
        .numberOfTaps(2)
        .onEnd(() => {
            if (escala.value > 1) {
                voltar();
            } else {
                escala.value = withTiming(ZOOM_DUPLO);
            }
        });

    const estilo = useAnimatedStyle(() => ({
        transform: [{ translateX: x.value }, { translateY: y.value }, { scale: escala.value }],
    }));

    return (
        <Modal visible transparent animationType="fade" onRequestClose={onClose} statusBarTranslucent>
            {/* O Modal abre fora da árvore principal: os gestos precisam de raiz própria. */}
            <GestureHandlerRootView style={styles.fundo}>
                <GestureDetector gesture={Gesture.Simultaneous(pinca, arraste, toqueDuplo)}>
                    <Animated.View style={[styles.area, estilo]}>
                        <Image
                            source={{ uri }}
                            style={styles.imagem}
                            contentFit="contain"
                            accessibilityLabel={titulo ?? 'Foto do material'}
                        />
                    </Animated.View>
                </GestureDetector>

                <View style={[styles.topo, { paddingTop: insets.top + 8 }]}>
                    <Text style={styles.titulo} numberOfLines={2}>
                        {titulo ?? ''}
                    </Text>
                    <Pressable
                        accessibilityRole="button"
                        accessibilityLabel="Fechar foto"
                        hitSlop={12}
                        onPress={onClose}
                        style={styles.fechar}
                    >
                        <Ionicons name="close" size={24} color="#ffffff" />
                    </Pressable>
                </View>
                <Text style={[styles.dica, { paddingBottom: insets.bottom + 16 }]}>
                    Use dois dedos ou toque duas vezes para ampliar
                </Text>
            </GestureHandlerRootView>
        </Modal>
    );
}

const styles = StyleSheet.create({
    fundo: { flex: 1, backgroundColor: 'rgba(0,0,0,0.94)' },
    area: { flex: 1 },
    imagem: { flex: 1 },
    topo: {
        position: 'absolute',
        top: 0,
        left: 0,
        right: 0,
        flexDirection: 'row',
        alignItems: 'center',
        gap: 12,
        paddingHorizontal: 16,
    },
    titulo: { flex: 1, color: '#ffffff', fontSize: 15, fontWeight: '700' },
    fechar: {
        width: 40,
        height: 40,
        borderRadius: 20,
        alignItems: 'center',
        justifyContent: 'center',
        backgroundColor: 'rgba(255,255,255,0.15)',
    },
    dica: { position: 'absolute', bottom: 0, left: 0, right: 0, textAlign: 'center', color: 'rgba(255,255,255,0.7)', fontSize: 12 },
});
