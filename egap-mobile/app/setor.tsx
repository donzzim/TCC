import { useRouter } from 'expo-router';
import { View } from 'react-native';

import { Text } from '@/src/components/Text';
import { GradientHeader } from '@/src/components/GradientHeader';
import { Screen } from '@/src/components/Screen';
import { SetorLista } from '@/src/components/SetorSheet';
import { useTheme } from '@/src/theme/ThemeProvider';

export default function EscolherSetor() {
    const router = useRouter();
    const { colors } = useTheme();

    return (
        <Screen safeTop={false}>
            <GradientHeader title="Escolha o setor" subtitle="Você tem mais de uma lotação" overlap={8} />
            <View style={{ padding: 16, gap: 16 }}>
                <Text style={{ color: colors.textMuted }}>
                    Os bens, a conferência e os pedidos serão os do setor escolhido. Você pode trocar depois, no
                    perfil.
                </Text>
                <SetorLista onTrocado={() => router.replace('/(tabs)')} />
            </View>
        </Screen>
    );
}
