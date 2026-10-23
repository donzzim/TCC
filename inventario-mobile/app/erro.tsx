import { useQueryClient } from '@tanstack/react-query';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { View } from 'react-native';

import { EmptyState } from '@/src/components/EmptyState';
import { useTheme } from '@/src/theme/ThemeProvider';

const ORIGENS: Record<string, string> = {
    bens: 'os bens',
    conferencia: 'a conferência',
    pedidos: 'os pedidos',
    dashboard: 'o painel',
};

export default function Erro() {
    const { colors } = useTheme();
    const router = useRouter();
    const queryClient = useQueryClient();
    const { origem } = useLocalSearchParams<{ origem?: string }>();
    const o = origem ? ORIGENS[origem] : undefined;

    const tentarNovamente = () => {
        if (router.canGoBack()) router.back();
        else router.replace('/');
        void queryClient.invalidateQueries();
    };

    return (
        <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: colors.background }}>
            <EmptyState
                title="Não foi possível carregar"
                message={`Não conseguimos carregar ${o ?? 'os dados'}. Verifique sua conexão e tente novamente.`}
                actionLabel="Tentar novamente"
                onAction={tentarNovamente}
            />
        </View>
    );
}
