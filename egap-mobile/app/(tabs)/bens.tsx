import { Ionicons } from '@expo/vector-icons';
import { FlashList } from '@shopify/flash-list';
import { useRouter } from 'expo-router';
import { useEffect, useMemo, useState } from 'react';
import { ActivityIndicator, StyleSheet, View } from 'react-native';

import { Text, TextInput } from '@/src/components/Text';
import { BemRow } from '@/src/components/BemRow';
import { EmptyState } from '@/src/components/EmptyState';
import { GradientHeader } from '@/src/components/GradientHeader';
import { useBens } from '@/src/hooks/useBens';
import { useErroRede } from '@/src/hooks/useErroRede';
import { useTheme } from '@/src/theme/ThemeProvider';
import { radius } from '@/src/theme/tokens';
import { numero } from '@/src/utils/format';

export default function Bens() {
    const { colors } = useTheme();
    const router = useRouter();
    const [texto, setTexto] = useState('');
    const [busca, setBusca] = useState('');

    useEffect(() => {
        const t = setTimeout(() => setBusca(texto), 300);

        return () => clearTimeout(t);
    }, [texto]);

    const query = useBens(busca);
    const itens = useMemo(() => query.data?.pages.flatMap((p) => p.data) ?? [], [query.data]);
    const total = query.data?.pages[0]?.meta.total;

    // Erro inicial vai para a tela de erro; falha ao paginar/atualizar a lista mostra aviso inline.
    useErroRede(query.isError && !query.data ? query.error : null, 'bens');

    return (
        <View style={{ flex: 1, backgroundColor: colors.background }}>
            <GradientHeader
                title="Bens"
                subtitle={
                    total === undefined
                        ? undefined
                        : busca.trim()
                          ? `${numero(total)} ${total === 1 ? 'encontrado' : 'encontrados'}`
                          : `${numero(total)} no setor`
                }
                overlap={28}
            />
            <View style={[styles.busca, { backgroundColor: colors.card, borderColor: colors.border }]}>
                <Ionicons name="search" size={18} color={colors.textMuted} />
                <TextInput
                    value={texto}
                    onChangeText={setTexto}
                    placeholder="Buscar por patrimônio, descrição, série…"
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
                    data={itens}
                    keyExtractor={(b) => String(b.id)}
                    contentContainerStyle={{ paddingHorizontal: 14, paddingBottom: 24 }}
                    ItemSeparatorComponent={() => <View style={{ height: 8 }} />}
                    renderItem={({ item }) => (
                        <BemRow
                            bem={item}
                            onPress={() =>
                                router.push({
                                    pathname: '/bem/[patrimonio]',
                                    params: { patrimonio: String(item.patrimonio) },
                                })
                            }
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
                            <EmptyState
                                title="Nenhum bem encontrado"
                                message={busca ? 'Tente outro termo de busca.' : 'Este setor não possui bens.'}
                            />
                        )
                    }
                    ListFooterComponent={
                        query.isFetchingNextPage ? (
                            <ActivityIndicator color={colors.primary} style={{ margin: 16 }} />
                        ) : query.isError && itens.length > 0 ? (
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
});
