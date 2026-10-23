import { CameraView, useCameraPermissions } from 'expo-camera';
import { useRef } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { Text } from '@/src/components/Text';
import { useTheme } from '@/src/theme/ThemeProvider';
import { radius } from '@/src/theme/tokens';

interface Props {
    onCodigo: (codigo: string) => void;
    /** Pausa a leitura (ex.: enquanto consulta o código lido). */
    pausado?: boolean;
}

/** Câmera só para códigos de barras; ignora repetições do mesmo código em sequência. */
export function Scanner({ onCodigo, pausado = false }: Props) {
    const { colors } = useTheme();
    const [permissao, pedirPermissao] = useCameraPermissions();
    const ultimo = useRef<{ codigo: string; em: number } | null>(null);

    if (!permissao) {
        return <View style={styles.box} />;
    }

    if (!permissao.granted) {
        return (
            <View style={[styles.box, styles.centro]}>
                <Text style={styles.msg}>
                    {permissao.canAskAgain
                        ? 'Permita o uso da câmera para ler a plaqueta.'
                        : 'Câmera bloqueada. Habilite a permissão nas configurações ou digite o patrimônio abaixo.'}
                </Text>
                {permissao.canAskAgain ? (
                    <Pressable
                        accessibilityRole="button"
                        onPress={() => void pedirPermissao()}
                        style={[styles.botao, { backgroundColor: colors.primary }]}
                    >
                        <Text style={{ color: colors.onPrimary, fontWeight: '700' }}>Permitir câmera</Text>
                    </Pressable>
                ) : null}
            </View>
        );
    }

    return (
        <View style={styles.box}>
            <CameraView
                style={StyleSheet.absoluteFill}
                facing="back"
                barcodeScannerSettings={{ barcodeTypes: ['code128', 'code39', 'ean13', 'ean8', 'itf14'] }}
                onBarcodeScanned={
                    pausado
                        ? undefined
                        : ({ data }) => {
                              const agora = Date.now();

                              if (ultimo.current?.codigo === data && agora - ultimo.current.em < 2500) {
                                  return;
                              }
                              ultimo.current = { codigo: data, em: agora };
                              onCodigo(data);
                          }
                }
            />
            <View pointerEvents="none" style={styles.moldura} />
        </View>
    );
}

const styles = StyleSheet.create({
    box: { height: 280, borderRadius: radius.card, overflow: 'hidden', backgroundColor: '#0b1220' },
    centro: { alignItems: 'center', justifyContent: 'center', padding: 20, gap: 12 },
    msg: { color: '#e2e8f0', textAlign: 'center', fontSize: 14 },
    botao: { paddingHorizontal: 18, paddingVertical: 10, borderRadius: radius.pill },
    moldura: {
        position: 'absolute',
        top: '25%',
        bottom: '25%',
        left: '10%',
        right: '10%',
        borderWidth: 2,
        borderColor: 'rgba(255,255,255,0.8)',
        borderRadius: 12,
    },
});
