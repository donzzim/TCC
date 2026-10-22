import { Ionicons } from '@expo/vector-icons';
import { FlashList } from '@shopify/flash-list';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useEffect, useMemo, useState } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, TextInput, View } from 'react-native';

import type { Material, PedidoTipo } from '@/src/api/types';
import { QUANTIDADE_MAX, useCarrinho } from '@/src/cart/CartProvider';
import { Card } from '@/src/components/Card';
import { EmptyState } from '@/src/components/EmptyState';
import { Folha } from '@/src/components/Folha';
import { GradientHeader } from '@/src/components/GradientHeader';
import { useErroRede } from '@/src/hooks/useErroRede';
import { useMateriais } from '@/src/hooks/usePedidos';
import { useTheme } from '@/src/theme/ThemeProvider';
import { radius } from '@/src/theme/tokens';
import { moeda, numero } from '@/src/utils/format';

export default function NovoPedido() {
    const { colors } = useTheme();
    const router = useRouter();
    const params = useLocalSearchParams<{ tipo?: string }>();
    const tipo: PedidoTipo = params.tipo === 'permanente' ? 'permanente' : 'consumo';
    const { carrinho, adicionar } = useCarrinho();
    const itensCarrinho = carrinho(tipo).itens;

    const [texto, setTexto] = useState('');
    const [busca, setBusca] = useState('');
    const [selecionado, setSelecionado] = useState<Material | null>(null);
    const [quantidade, setQuantidade] = useState('1');

    useEffect(() => {
        const t = setTimeout(() => setBusca(texto), 300);

        return () => clearTimeout(t);
    }, [texto]);

    const query = useMateriais(tipo, busca);
    const materiais = useMemo(() => query.data?.pages.flatMap((p) => p.materiais) ?? [], [query.data]);
    const noCarrinho = useMemo(() => new Map(itensCarrinho.map((i) => [i.materialId, i.quantidade])), [itensCarrinho]);

    useErroRede(query.isError && !query.data ? query.error : null, 'pedidos');

    const abrir = (material: Material) => {
        setSelecionado(material);
        setQuantidade(String(noCarrinho.get(material.id) ?? 1));
    };

    const qtd = Number(quantidade);
    const qtdValida = /^\d+$/.test(quantidade) && qtd >= 1 && qtd <= QUANTIDADE_MAX;

    const confirmar = () => {
        if (!selecionado || !qtdValida) return;

        adicionar(tipo, selecionado, qtd);
        setSelecionado(null);
    };

    return (
        <View style={{ flex: 1, backgroundColor: colors.background }}>
            <GradientHeader
                title={tipo === 'consumo' ? 'Material de consumo' : 'Material permanente'}
                subtitle="Novo pedido"
                overlap={28}
                right={
                    <Pressable accessibilityRole="button" accessibilityLabel="Voltar" onPress={() => router.back()} hitSlop={10}>
                        <Ionicons name="close" size={26} color="#ffffff" />
                    </Pressable>
                }
            />
            <View style={[styles.busca, { backgroundColor: colors.card, borderColor: colors.border }]}>
                <Ionicons name="search" size={18} color={colors.textMuted} />
                <TextInput
                    value={texto}
                    onChangeText={setTexto}
                    placeholder="Buscar material…"
                    placeholderTextColor={colors.textMuted}
                    autoCapitalize="none"
                    autoCorrect={false}
                    returnKeyType="search"
                    style={[styles.input, { color: colors.text }]}
                />
            </View>

            {query.isPending ? (
                <ActivityIndicator color={colors.primary} style={{ marginTop: 32 }} />
            ) : (
                <FlashList
                    data={materiais}
                    keyExtractor={(m) => String(m.id)}
                    contentContainerStyle={{ paddingHorizontal: 14, paddingBottom: 96 }}
                    ItemSeparatorComponent={() => <View style={{ height: 8 }} />}
                    renderItem={({ item }) => {
                        const naCesta = noCarrinho.get(item.id);

                        return (
                            <Pressable accessibilityRole="button" onPress={() => abrir(item)}>
                                <Card style={{ gap: 4 }}>
                                    <Text style={{ color: colors.text, fontWeight: '700', fontSize: 14 }} numberOfLines={3}>
                                        {item.descricao}
                                    </Text>
                                    <Text style={{ color: colors.textMuted, fontSize: 12 }}>
                                        {item.unidade}
                                        {item.preco_medio > 0 ? ` · ${moeda(item.preco_medio)}` : ''}
                                        {item.quantidade_estoque !== null
                                            ? ` · ${item.disponivel ? `${numero(item.quantidade_estoque)} em estoque` : 'Sem estoque'}`
                                            : ''}
                                    </Text>
                                    {naCesta ? (
                                        <Text style={{ color: colors.success, fontSize: 12, fontWeight: '700' }}>
                                            No carrinho: {numero(naCesta)}
                                        </Text>
                                    ) : null}
                                </Card>
                            </Pressable>
                        );
                    }}
                    onEndReachedThreshold={0.5}
                    onEndReached={() => {
                        if (query.hasNextPage && !query.isFetchingNextPage) {
                            void query.fetchNextPage();
                        }
                    }}
                    ListEmptyComponent={
                        query.isError ? (
                            <EmptyState
                                title="Não foi possível carregar"
                                message={query.error.message}
                                actionLabel="Tentar novamente"
                                onAction={() => void query.refetch()}
                            />
                        ) : (
                            <EmptyState
                                title="Nenhum material encontrado"
                                message={busca ? 'Tente outro termo de busca.' : 'Não há materiais disponíveis.'}
                            />
                        )
                    }
                    ListFooterComponent={
                        query.isFetchingNextPage ? (
                            <ActivityIndicator color={colors.primary} style={{ margin: 16 }} />
                        ) : query.isError && materiais.length > 0 ? (
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

            {itensCarrinho.length > 0 ? (
                <Pressable
                    accessibilityRole="button"
                    onPress={() => router.push({ pathname: '/pedidos/carrinho', params: { tipo } })}
                    style={[styles.fab, { backgroundColor: colors.primary }]}
                >
                    <Ionicons name="cart" size={20} color={colors.onPrimary} />
                    <Text style={{ color: colors.onPrimary, fontWeight: '800' }}>
                        Ver carrinho ({itensCarrinho.length})
                    </Text>
                </Pressable>
            ) : null}

            <Folha visible={selecionado !== null} title="Adicionar ao carrinho" onClose={() => setSelecionado(null)}>
                <Text style={{ color: colors.text }}>{selecionado?.descricao}</Text>
                <Text style={{ color: colors.textMuted, fontSize: 12 }}>Quantidade (1 a {numero(QUANTIDADE_MAX)})</Text>
                <TextInput
                    value={quantidade}
                    onChangeText={(v) => setQuantidade(v.replace(/\D+/g, ''))}
                    keyboardType="number-pad"
                    autoFocus
                    onSubmitEditing={confirmar}
                    style={[styles.campo, { color: colors.text, borderColor: qtdValida ? colors.border : colors.danger }]}
                />
                {!qtdValida ? (
                    <Text style={{ color: colors.danger, fontSize: 12 }}>
                        Informe uma quantidade entre 1 e {numero(QUANTIDADE_MAX)}.
                    </Text>
                ) : null}
                <Pressable
                    accessibilityRole="button"
                    disabled={!qtdValida}
                    onPress={confirmar}
                    style={[styles.botao, { backgroundColor: colors.primary, opacity: qtdValida ? 1 : 0.5 }]}
                >
                    <Text style={{ color: colors.onPrimary, fontWeight: '800' }}>
                        {selecionado && noCarrinho.has(selecionado.id) ? 'Atualizar quantidade' : 'Adicionar'}
                    </Text>
                </Pressable>
            </Folha>
        </View>
    );
}

const styles = StyleSheet.create({
    busca: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 8,
        marginHorizontal: 14,
        marginTop: -22,
        marginBottom: 12,
        paddingHorizontal: 12,
        borderRadius: radius.input,
        borderWidth: StyleSheet.hairlineWidth,
        shadowColor: '#0f172a',
        shadowOpacity: 0.12,
        shadowRadius: 10,
        shadowOffset: { width: 0, height: 4 },
        elevation: 3,
    },
    input: { flex: 1, paddingVertical: 12, fontSize: 14 },
    fab: {
        position: 'absolute',
        bottom: 20,
        alignSelf: 'center',
        flexDirection: 'row',
        alignItems: 'center',
        gap: 8,
        paddingHorizontal: 20,
        paddingVertical: 14,
        borderRadius: radius.pill,
        shadowColor: '#0f172a',
        shadowOpacity: 0.25,
        shadowRadius: 10,
        shadowOffset: { width: 0, height: 4 },
        elevation: 5,
    },
    campo: { borderWidth: 1, borderRadius: radius.input, paddingHorizontal: 12, paddingVertical: 10, fontSize: 16 },
    botao: { alignItems: 'center', paddingVertical: 14, borderRadius: radius.pill },
});
