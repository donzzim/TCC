import { Ionicons } from '@expo/vector-icons';
import { useLocalSearchParams, useRouter } from 'expo-router';
import type { ReactNode } from 'react';
import { ActivityIndicator, Pressable, ScrollView, StyleSheet, View } from 'react-native';

import { Text } from '@/src/components/Text';
import { ApiError } from '@/src/api/client';
import type { Bem } from '@/src/api/types';
import { Card } from '@/src/components/Card';
import { EmptyState } from '@/src/components/EmptyState';
import { Foto } from '@/src/components/Foto';
import { GradientHeader } from '@/src/components/GradientHeader';
import { useBem } from '@/src/hooks/useBens';
import { useErroRede } from '@/src/hooks/useErroRede';
import { useTheme } from '@/src/theme/ThemeProvider';
import { moeda } from '@/src/utils/format';

function dataCurta(iso: string | null | undefined): string | null {
    if (!iso) return null;

    const d = new Date(iso.length === 10 ? `${iso}T00:00:00` : iso);

    return Number.isNaN(d.getTime()) ? null : d.toLocaleDateString('pt-BR');
}

function dinheiro(v: string | number | null | undefined): string | null {
    if (v === null || v === undefined || v === '') return null;

    const n = Number(v);

    return Number.isFinite(n) ? moeda(n) : null;
}

type Campo = [string, string | number | null | undefined];

function Secao({ titulo, campos }: { titulo: string; campos: Campo[] }) {
    const { colors } = useTheme();
    const preenchidos = campos.filter(([, v]) => v !== null && v !== undefined && v !== '');

    if (preenchidos.length === 0) return null;

    return (
        <Card style={{ gap: 10 }}>
            <Text style={[styles.secao, { color: colors.text }]}>{titulo}</Text>
            {preenchidos.map(([rotulo, valor]) => (
                <View key={rotulo}>
                    <Text style={{ color: colors.textMuted, fontSize: 11 }}>{rotulo}</Text>
                    <Text style={{ color: colors.text, fontSize: 14 }} selectable>
                        {String(valor)}
                    </Text>
                </View>
            ))}
        </Card>
    );
}

function Detalhe({ bem }: { bem: Bem }): ReactNode {
    const { colors } = useTheme();

    return (
        <>
            {bem.imagem_url ? (
                <Card style={{ padding: 8 }}>
                    <Foto
                        uri={bem.imagem_url}
                        titulo={bem.descricao_resumida || bem.descricao}
                        larga
                        tamanho={220}
                        arredondamento={12}
                    />
                    <Text style={[styles.legenda, { color: colors.textMuted }]}>Toque na foto para ampliar</Text>
                </Card>
            ) : null}
            {bem.pertence_ao_setor === false ? (
                <View style={[styles.banner, { backgroundColor: `${colors.warning}1f`, borderColor: colors.warning }]}>
                    <Ionicons name="warning-outline" size={20} color={colors.warning} />
                    <Text style={{ color: colors.text, flex: 1, fontSize: 13 }}>
                        Este bem pertence a outro setor ({bem.setor.nome ?? 'não informado'}). Somente consulta.
                    </Text>
                </View>
            ) : null}
            <Secao
                titulo="Identificação"
                campos={[
                    ['Patrimônio', bem.patrimonio],
                    ['Patrimônio anterior', bem.patrimonio_anterior],
                    ['Tombo SMARAPD', bem.tombo_smarapd ?? bem.num_tombo_smarapd],
                    ['Número de série', bem.numero_serie],
                    ['Situação', bem.situacao],
                ]}
            />
            <Secao
                titulo="Características"
                campos={[
                    ['Descrição', bem.descricao],
                    ['Tipo', bem.tipo_bem],
                    ['Marca', bem.marca],
                    ['Modelo', bem.modelo],
                    ['Estado de conservação', bem.estado_conservacao],
                    ['Voltagem', bem.voltagem],
                ]}
            />
            <Secao
                titulo="Localização"
                campos={[
                    ['Unidade judiciária', bem.unidade_judiciaria.nome],
                    ['Setor', bem.setor.nome],
                    ['Complemento', bem.complemento_setor.nome],
                    ['Andar', bem.andar_setor],
                ]}
            />
            <Secao
                titulo="Valores e documentos"
                campos={[
                    ['Valor de aquisição', dinheiro(bem.valor_aquisicao)],
                    ['Valor atual', dinheiro(bem.valor)],
                    ['Data de incorporação', dataCurta(bem.data_incorporacao)],
                    ['Data de cadastro', dataCurta(bem.data_cadastro)],
                    ['Nº do processo', bem.numero_processo],
                    ['Nota de empenho', bem.nota_empenho],
                    ['Nota de liquidação', bem.nota_liquidacao],
                    ['Data de liquidação', dataCurta(bem.data_liquidacao)],
                    ['Data de baixa', dataCurta(bem.data_baixa)],
                    ['Processo de baixa', bem.processo_baixa],
                ]}
            />
            <Secao titulo="Observação" campos={[['Observação', bem.observacao]]} />
        </>
    );
}

export default function BemDetalhe() {
    const { colors } = useTheme();
    const router = useRouter();
    const { patrimonio } = useLocalSearchParams<{ patrimonio: string }>();
    const query = useBem(patrimonio);
    const naoEncontrado = query.error instanceof ApiError && query.error.status === 404;

    useErroRede(query.error, 'bens');

    return (
        <View style={{ flex: 1, backgroundColor: colors.background }}>
            <GradientHeader
                subtitle={`Patrimônio ${patrimonio ?? ''}`}
                title={query.data?.descricao_resumida || query.data?.descricao || 'Detalhe do bem'}
                overlap={20}
                right={
                    <Pressable accessibilityRole="button" accessibilityLabel="Voltar" onPress={() => router.back()} hitSlop={10}>
                        <Ionicons name="close" size={26} color="#ffffff" />
                    </Pressable>
                }
            />
            <ScrollView contentContainerStyle={styles.corpo}>
                {query.isPending ? <ActivityIndicator color={colors.primary} style={{ marginTop: 24 }} /> : null}
                {naoEncontrado ? (
                    <EmptyState title="Bem não encontrado" message="Verifique o número do patrimônio." />
                ) : query.isError ? (
                    <EmptyState
                        title="Não foi possível carregar"
                        message={query.error.message}
                        actionLabel="Tentar novamente"
                        onAction={() => void query.refetch()}
                    />
                ) : null}
                {query.data ? <Detalhe bem={query.data} /> : null}
            </ScrollView>
        </View>
    );
}

const styles = StyleSheet.create({
    legenda: { fontSize: 11, textAlign: 'center', marginTop: 6 },
    corpo: { padding: 14, gap: 12, paddingBottom: 32 },
    secao: { fontSize: 14, fontWeight: '800' },
    banner: { flexDirection: 'row', gap: 10, alignItems: 'center', padding: 12, borderRadius: 14, borderWidth: 1 },
});
