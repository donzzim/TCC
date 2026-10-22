import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { Card } from '@/src/components/Card';
import { GradientHeader } from '@/src/components/GradientHeader';
import { Screen } from '@/src/components/Screen';
import { useTheme } from '@/src/theme/ThemeProvider';

type IconName = React.ComponentProps<typeof Ionicons>['name'];

const PASSOS: { icone: IconName; titulo: string; texto: string }[] = [
    {
        icone: 'home-outline',
        titulo: 'Início',
        texto: 'Resumo do seu setor: total de bens, valores, andamento da conferência e pedidos abertos.',
    },
    {
        icone: 'cube-outline',
        titulo: 'Bens',
        texto: 'Pesquise os bens do setor por patrimônio, descrição ou número de série e veja os detalhes.',
    },
    {
        icone: 'scan-outline',
        titulo: 'Ler plaqueta',
        texto: 'O botão central abre a câmera. Fora da conferência ele consulta o bem; na aba Conferência ele registra o bem como localizado.',
    },
    {
        icone: 'checkmark-done-outline',
        titulo: 'Conferência',
        texto: 'Acompanhe o inventário em andamento, marque bens não localizados ou com divergência e finalize a conferência.',
    },
    {
        icone: 'cart-outline',
        titulo: 'Pedidos',
        texto: 'Consulte o histórico e monte novos pedidos de material de consumo ou permanente.',
    },
    {
        icone: 'swap-horizontal-outline',
        titulo: 'Troca de setor',
        texto: 'Se você tem mais de uma lotação, troque de setor no Perfil. Os dados do app mudam junto.',
    },
];

export default function Tutorial() {
    const { colors } = useTheme();
    const router = useRouter();

    return (
        <Screen safeTop={false}>
            <GradientHeader
                title="Como usar o app"
                overlap={8}
                right={
                    <Pressable
                        accessibilityRole="button"
                        accessibilityLabel="Voltar"
                        onPress={() => router.back()}
                        hitSlop={8}
                    >
                        <Ionicons name="close" size={26} color="#ffffff" />
                    </Pressable>
                }
            />
            <View style={styles.corpo}>
                {PASSOS.map((passo) => (
                    <Card key={passo.titulo} style={styles.passo}>
                        <View style={[styles.icone, { backgroundColor: colors.track }]}>
                            <Ionicons name={passo.icone} size={22} color={colors.primary} />
                        </View>
                        <View style={styles.textos}>
                            <Text style={[styles.titulo, { color: colors.text }]}>{passo.titulo}</Text>
                            <Text style={[styles.texto, { color: colors.textMuted }]}>{passo.texto}</Text>
                        </View>
                    </Card>
                ))}
            </View>
        </Screen>
    );
}

const styles = StyleSheet.create({
    corpo: { padding: 16, gap: 12 },
    passo: { flexDirection: 'row', gap: 14, alignItems: 'flex-start' },
    icone: { width: 42, height: 42, borderRadius: 14, alignItems: 'center', justifyContent: 'center' },
    textos: { flex: 1 },
    titulo: { fontSize: 15, fontWeight: '800' },
    texto: { fontSize: 13, marginTop: 2, lineHeight: 19 },
});
