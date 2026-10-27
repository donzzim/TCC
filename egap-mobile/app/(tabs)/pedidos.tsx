import { Ionicons } from '@expo/vector-icons';
import { FlashList } from '@shopify/flash-list';
import { useRouter } from 'expo-router';
import { useMemo } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, View } from 'react-native';

import { Text } from '@/src/components/Text';
import type { Pedido, PedidoTipo } from '@/src/api/types';
import { Card } from '@/src/components/Card';
import { EmptyState } from '@/src/components/EmptyState';
import { Foto } from '@/src/components/Foto';
import { GradientHeader } from '@/src/components/GradientHeader';
import { useErroRede } from '@/src/hooks/useErroRede';
import { usePedidos } from '@/src/hooks/usePedidos';
import { useSessao } from '@/src/session/SessionProvider';
import { useTheme } from '@/src/theme/ThemeProvider';
import { radius } from '@/src/theme/tokens';
import { dataHora, numero } from '@/src/utils/format';
import { corDaSituacao } from '@/src/utils/situacaoPedido';

const ROTULO_TIPO: Record<PedidoTipo, string> = { consumo: 'Consumo', permanente: 'Permanente' };

function PedidoCard({ pedido, onPress }: { pedido: Pedido; onPress: () => void }) {
    const { colors } = useTheme();
    const visiveis = pedido.itens.slice(0, 3);
    const restantes = pedido.itens.length - visiveis.length;
    const cor = corDaSituacao(pedido.situacao.id, colors);

    return (
        <Pressable accessibilityRole="button" accessibilityLabel={`Abrir pedido ${pedido.id}`} onPress={onPress}>
            <Card style={{ gap: 8 }}>
                <View style={styles.linha}>
                    <Text style={[styles.numero, { color: colors.text }]}>Pedido #{pedido.id}</Text>
                    <View style={styles.direita}>
                        <View style={[styles.selo, { backgroundColor: `${cor}1f` }]}>
                            <Text style={{ color: cor, fontSize: 11, fontWeight: '700' }}>
                                {pedido.situacao.descricao ?? 'Em análise'}
                            </Text>
                        </View>
                        <Ionicons name="chevron-forward" size={16} color={colors.textMuted} />
                    </View>
                </View>
                <Text style={{ color: colors.textMuted, fontSize: 12 }}>
                    {ROTULO_TIPO[pedido.tipo]} · {dataHora(pedido.data)}
                    {pedido.complemento_setor.descricao ? ` · ${pedido.complemento_setor.descricao}` : ''}
                </Text>
                {visiveis.map((item) => (
                    <View key={item.id} style={styles.item}>
                        <Foto uri={item.imagem_url} titulo={item.material} tamanho={32} arredondamento={8} />
                        <Text style={{ color: colors.text, fontSize: 13, flex: 1 }} numberOfLines={1}>
                            {numero(item.quantidade)}× {item.material ?? 'Material'}
                        </Text>
                    </View>
                ))}
                {restantes > 0 ? (
                    <Text style={{ color: colors.textMuted, fontSize: 12 }}>
                        + {restantes} {restantes === 1 ? 'item' : 'itens'}
                    </Text>
                ) : null}
            </Card>
        </Pressable>
    );
}

export default function Pedidos() {
    const { colors } = useTheme();
    const router = useRouter();
    const { sessao } = useSessao();
    // o solicitante do pedido é o cadastro EGAP (achado pelo CPF); sem ele não há pedidos
    const semCadastroEgap = !!sessao && sessao.egap_id === null;
    const query = usePedidos(!semCadastroEgap);
    const pedidos = useMemo(() => query.data?.pages.flatMap((p) => p.pedidos) ?? [], [query.data]);

    useErroRede(query.isError && !query.data ? query.error : null, 'pedidos');

    const novo = (tipo: PedidoTipo, rotulo: string, icone: keyof typeof Ionicons.glyphMap) => (
        <Pressable
            accessibilityRole="button"
            onPress={() => router.push({ pathname: '/pedidos/novo', params: { tipo } })}
            style={[styles.botao, { backgroundColor: colors.card, borderColor: colors.border }]}
        >
            <Ionicons name={icone} size={20} color={colors.primary} />
            <Text style={{ color: colors.text, fontWeight: '700', flex: 1 }}>{rotulo}</Text>
        </Pressable>
    );

    return (
        <View style={{ flex: 1, backgroundColor: colors.background }}>
            <GradientHeader title="Pedidos" subtitle="Histórico do setor" overlap={8} />
            {semCadastroEgap ? (
                <EmptyState
                    title="Sem cadastro no EGAP"
                    message="Seu usuário não tem cadastro no EGAP, que é feito pelo CPF. Procure a administração para poder fazer pedidos."
                />
            ) : (
                <View style={styles.botoes}>
                    {novo('consumo', 'Novo pedido de consumo', 'cart-outline')}
                    {novo('permanente', 'Novo pedido de permanente', 'desktop-outline')}
                </View>
            )}

            {semCadastroEgap ? null : query.isPending ? (
                <ActivityIndicator color={colors.primary} style={{ marginTop: 32 }} />
            ) : (
                <FlashList
                    data={pedidos}
                    keyExtractor={(p) => String(p.id)}
                    contentContainerStyle={{ paddingHorizontal: 14, paddingBottom: 24 }}
                    ItemSeparatorComponent={() => <View style={{ height: 8 }} />}
                    renderItem={({ item }) => (
                        <PedidoCard
                            pedido={item}
                            onPress={() => router.push({ pathname: '/pedidos/[id]', params: { id: item.id } })}
                        />
                    )}
                    onEndReachedThreshold={0.5}
                    onEndReached={() => {
                        if (query.hasNextPage && !query.isFetchingNextPage) {
                            void query.fetchNextPage();
                        }
                    }}
                    refreshing={query.isRefetching && !query.isFetchingNextPage}
                    onRefresh={() => void query.refetch()}
                    ListEmptyComponent={
                        query.isError ? (
                            <EmptyState
                                title="Não foi possível carregar"
                                message={query.error.message}
                                actionLabel="Tentar novamente"
                                onAction={() => void query.refetch()}
                            />
                        ) : (
                            <EmptyState title="Nenhum pedido ainda" message="Seus pedidos aparecerão aqui." />
                        )
                    }
                    ListFooterComponent={
                        query.isFetchingNextPage ? (
                            <ActivityIndicator color={colors.primary} style={{ margin: 16 }} />
                        ) : query.isError && pedidos.length > 0 ? (
                            <Text
                                onPress={() => void query.fetchNextPage()}
                                style={{ color: colors.danger, textAlign: 'center', padding: 16 }}
                            >
                                Falha ao carregar mais. Toque para tentar novamente.
                            </Text>
                        ) : null
                    }
                />
            )}
        </View>
    );
}

const styles = StyleSheet.create({
    botoes: { paddingHorizontal: 14, paddingTop: 12, paddingBottom: 12, gap: 8 },
    botao: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 10,
        padding: 14,
        borderRadius: radius.input,
        borderWidth: StyleSheet.hairlineWidth,
    },
    linha: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 8 },
    direita: { flexDirection: 'row', alignItems: 'center', gap: 4 },
    item: { flexDirection: 'row', alignItems: 'center', gap: 8 },
    numero: { fontSize: 15, fontWeight: '800' },
    selo: { paddingHorizontal: 10, paddingVertical: 3, borderRadius: radius.pill },
});
