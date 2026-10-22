import { GradientHeader } from '@/src/components/GradientHeader';
import { EmptyState } from '@/src/components/EmptyState';
import { Screen } from '@/src/components/Screen';

// Placeholder: o conteúdo é implementado em tarefa posterior.
export default function Pedidos() {
    return (
        <Screen safeTop={false}>
            <GradientHeader title="Pedidos" overlap={8} />
            <EmptyState title="Em construção" message="Esta aba será preenchida em breve." />
        </Screen>
    );
}
