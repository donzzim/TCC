import { useLocalSearchParams, useRouter } from 'expo-router';

import { EmptyState } from '@/src/components/EmptyState';
import { Screen } from '@/src/components/Screen';

// Placeholder: o scanner é implementado em tarefa posterior.
export default function Leitura() {
    const router = useRouter();
    const { modo } = useLocalSearchParams<{ modo?: 'consulta' | 'conferencia' }>();

    return (
        <Screen>
            <EmptyState
                title="Leitura de plaqueta"
                message={`Modo: ${modo ?? 'consulta'}. O scanner será implementado em breve.`}
                actionLabel="Fechar"
                onAction={() => router.back()}
            />
        </Screen>
    );
}
