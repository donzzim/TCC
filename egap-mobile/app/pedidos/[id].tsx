import { Ionicons } from '@expo/vector-icons';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { ActivityIndicator, Pressable, RefreshControl, ScrollView, StyleSheet, View } from 'react-native';

import { ApiError } from '@/src/api/client';
import type { PedidoDetalhe, PedidoFase, PedidoItem } from '@/src/api/types';
import { Card } from '@/src/components/Card';
import { EmptyState } from '@/src/components/EmptyState';
import { Foto } from '@/src/components/Foto';
import { GradientHeader } from '@/src/components/GradientHeader';
import { Text } from '@/src/components/Text';
import { useErroRede } from '@/src/hooks/useErroRede';
import { usePedido } from '@/src/hooks/usePedidos';
import { useTheme } from '@/src/theme/ThemeProvider';
import { radius } from '@/src/theme/tokens';
import { dataHora, numero } from '@/src/utils/format';
import { corDaSituacao } from '@/src/utils/situacaoPedido';

function Selo({ situacao }: { situacao: PedidoItem['situacao'] }) {
    const { colors } = useTheme();
    const cor = corDaSituacao(situacao.id, colors);

    return (
        <View style={[styles.selo, { backgroundColor: `${cor}1f` }]}>
            <Text style={{ color: cor, fontSize: 11, fontWeight: '700' }}>{situacao.descricao ?? 'Sem situação'}</Text>
        </View>
    );
}

function Item({ item }: { item: PedidoItem }) {
    const { colors } = useTheme();
    const atendido = item.quantidade_atendida > 0;

    return (
        <View style={styles.item}>
            <Foto uri={item.imagem_url} titulo={item.material} tamanho={40} arredondamento={10} />
            <View style={{ flex: 1, gap: 4 }}>
                <Text style={{ color: colors.text, fontSize: 14 }} numberOfLines={2}>
                    {item.material ?? 'Material'}
                </Text>
                <View style={styles.linha}>
                    <Text style={{ color: colors.textMuted, fontSize: 12 }}>
                        {atendido
                            ? `Atendidos ${numero(item.quantidade_atendida)} de ${numero(item.quantidade)}`
                            : `Quantidade: ${numero(item.quantidade)}`}
                    </Text>
                    <Selo situacao={item.situacao} />
                </View>
            </View>
        </View>
    );
}

function Fase({ fase, ultima }: { fase: PedidoFase; ultima: boolean }) {
    const { colors } = useTheme();
    const cor = corDaSituacao(fase.situacao.id, colors);
    const detalhe = [fase.material, fase.quantidade ? `${numero(fase.quantidade)} un.` : null]
        .filter(Boolean)
        .join(' · ');

    return (
        <View style={styles.fase}>
            <View style={styles.trilho}>
                <View style={[styles.ponto, { backgroundColor: cor }]} />
                {ultima ? null : <View style={[styles.linhaTempo, { backgroundColor: colors.border }]} />}
            </View>
            <View style={{ flex: 1, gap: 2, paddingBottom: ultima ? 0 : 16 }}>
                <Text style={{ color: cor, fontSize: 13, fontWeight: '800' }}>
                    {fase.situacao.descricao ?? 'Atualização'}
                </Text>
                {fase.descricao ? <Text style={{ color: colors.text, fontSize: 13 }}>{fase.descricao}</Text> : null}
                {detalhe ? <Text style={{ color: colors.textMuted, fontSize: 12 }}>{detalhe}</Text> : null}
                <Text style={{ color: colors.textMuted, fontSize: 11 }}>
                    {dataHora(fase.data)}
                    {fase.usuario ? ` · ${fase.usuario}` : ''}
                </Text>
            </View>
        </View>
    );
}

function Detalhe({ pedido }: { pedido: PedidoDetalhe }) {
    const { colors } = useTheme();

    return (
        <>
            <Card style={{ gap: 8 }}>
                <View style={styles.linha}>
                    <Text style={{ color: colors.textMuted, fontSize: 12 }}>Situação do pedido</Text>
                    <Selo situacao={pedido.situacao} />
                </View>
                <Text style={{ color: colors.textMuted, fontSize: 12 }}>
                    {pedido.tipo === 'consumo' ? 'Consumo' : 'Permanente'} · {dataHora(pedido.data)}
                </Text>
                {pedido.setor || pedido.complemento_setor.descricao ? (
                    <Text style={{ color: colors.text, fontSize: 13 }}>
                        {[pedido.setor, pedido.complemento_setor.descricao].filter(Boolean).join(' · ')}
                    </Text>
                ) : null}
                {pedido.observacao ? (
                    <Text style={{ color: colors.textMuted, fontSize: 13 }}>{pedido.observacao}</Text>
                ) : null}
            </Card>

            <Card style={{ gap: 12 }}>
                <Text style={[styles.secao, { color: colors.text }]}>
                    {pedido.itens.length === 1 ? '1 item' : `${pedido.itens.length} itens`}
                </Text>
                {pedido.itens.map((item) => (
                    <Item key={item.id} item={item} />
                ))}
            </Card>

            <Card style={{ gap: 12 }}>
                <Text style={[styles.secao, { color: colors.text }]}>Histórico</Text>
                {pedido.historico.length === 0 ? (
                    <Text style={{ color: colors.textMuted, fontSize: 13 }}>Nenhuma movimentação registrada.</Text>
                ) : (
                    <View>
                        {pedido.historico.map((fase, i) => (
                            <Fase key={fase.id} fase={fase} ultima={i === pedido.historico.length - 1} />
                        ))}
                    </View>
                )}
            </Card>
        </>
    );
}

export default function PedidoDetalheTela() {
    const { colors } = useTheme();
    const router = useRouter();
    const { id } = useLocalSearchParams<{ id: string }>();
    const query = usePedido(id);
    const naoEncontrado = query.error instanceof ApiError && query.error.status === 404;

    useErroRede(query.error, 'pedidos');

    return (
        <View style={{ flex: 1, backgroundColor: colors.background }}>
            <GradientHeader
                subtitle="Acompanhamento"
                title={`Pedido #${id ?? ''}`}
                overlap={20}
                right={
                    <Pressable
                        accessibilityRole="button"
                        accessibilityLabel="Voltar"
                        onPress={() => (router.canGoBack() ? router.back() : router.replace('/(tabs)/pedidos'))}
                        hitSlop={10}
                    >
                        <Ionicons name="close" size={26} color="#ffffff" />
                    </Pressable>
                }
            />
            <ScrollView
                contentContainerStyle={styles.corpo}
                refreshControl={
                    <RefreshControl refreshing={query.isRefetching} onRefresh={() => void query.refetch()} />
                }
            >
                {query.isPending ? <ActivityIndicator color={colors.primary} style={{ marginTop: 24 }} /> : null}
                {naoEncontrado ? (
                    <EmptyState title="Pedido não encontrado" message="Ele não existe ou não foi feito por você." />
                ) : query.isError ? (
                    <EmptyState
                        title="Não foi possível carregar"
                        message={query.error.message}
                        actionLabel="Tentar novamente"
                        onAction={() => void query.refetch()}
                    />
                ) : null}
                {query.data ? <Detalhe pedido={query.data} /> : null}
            </ScrollView>
        </View>
    );
}

const styles = StyleSheet.create({
    corpo: { padding: 14, gap: 12, paddingBottom: 32 },
    secao: { fontSize: 14, fontWeight: '800' },
    linha: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        gap: 8,
    },
    item: { flexDirection: 'row', alignItems: 'center', gap: 10 },
    selo: {
        paddingHorizontal: 10,
        paddingVertical: 3,
        borderRadius: radius.pill,
    },
    fase: { flexDirection: 'row', gap: 12 },
    trilho: { alignItems: 'center', width: 12 },
    ponto: { width: 12, height: 12, borderRadius: 6, marginTop: 3 },
    linhaTempo: { flex: 1, width: 2, marginTop: 4 },
});
