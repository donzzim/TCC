import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { Pressable } from 'react-native';

import { EmptyState } from '@/src/components/EmptyState';
import { GradientHeader } from '@/src/components/GradientHeader';
import { Screen } from '@/src/components/Screen';
import { useSessao } from '@/src/session/SessionProvider';

// Placeholder: o painel é implementado em tarefa posterior.
export default function Inicio() {
    const router = useRouter();
    const { sessao } = useSessao();

    return (
        <Screen safeTop={false}>
            <GradientHeader
                title={sessao?.usuario.nome ?? 'Início'}
                subtitle={sessao?.setor_atual ? `${sessao.setor_atual.setor} · ${sessao.setor_atual.unidade}` : undefined}
                overlap={8}
                right={
                    <Pressable
                        accessibilityRole="button"
                        accessibilityLabel="Perfil"
                        onPress={() => router.push('/perfil')}
                        hitSlop={8}
                    >
                        <Ionicons name="person-circle-outline" size={32} color="#ffffff" />
                    </Pressable>
                }
            />
            <EmptyState title="Em construção" message="O painel do setor será preenchido em breve." />
        </Screen>
    );
}
