import { Ionicons } from '@expo/vector-icons';
import { FlashList } from '@shopify/flash-list';
import { useRouter } from 'expo-router';
import { useMemo } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, View } from 'react-native';

import type { Notificacao } from '@/src/api/types';
import { EmptyState } from '@/src/components/EmptyState';
import { GradientHeader } from '@/src/components/GradientHeader';
import { Text } from '@/src/components/Text';
import { useErroRede } from '@/src/hooks/useErroRede';
import { rotaDaNotificacao, useMarcarLida, useMarcarTodas, useNotificacoes } from '@/src/hooks/useNotificacoes';
import { useTheme } from '@/src/theme/ThemeProvider';
import { radius } from '@/src/theme/tokens';
import { tempoRelativo } from '@/src/utils/format';

function ItemNotificacao({ notificacao, onPress }: { notificacao: Notificacao; onPress: () => void }) {
    const { colors } = useTheme();
    const naoLida = !notificacao.lida;

    return (
        <Pressable
            accessibilityRole="button"
            accessibilityLabel={`${naoLida ? 'Não lida: ' : ''}${notificacao.titulo}`}
            onPress={onPress}
            style={[
                styles.item,
                {
                    backgroundColor: naoLida ? `${colors.primary}14` : colors.card,
                    borderColor: naoLida ? `${colors.primary}55` : colors.border,
                },
            ]}
        >
            <View style={[styles.marcador, { backgroundColor: naoLida ? colors.primary : 'transparent' }]} />
            <View style={styles.textos}>
                <View style={styles.linha}>
                    <Text
                        style={[styles.titulo, { color: colors.text, fontWeight: naoLida ? '800' : '600' }]}
                        numberOfLines={2}
                    >
                        {notificacao.titulo}
                    </Text>
                    <Text style={{ color: colors.textMuted, fontSize: 11 }}>{tempoRelativo(notificacao.criada_em)}</Text>
                </View>
                <Text style={[styles.corpo, { color: colors.textMuted }]} numberOfLines={4}>
                    {notificacao.corpo}
                </Text>
            </View>
        </Pressable>
    );
}

export default function Notificacoes() {
    const { colors } = useTheme();
    const router = useRouter();
    const query = useNotificacoes();
    const marcarLida = useMarcarLida();
    const marcarTodas = useMarcarTodas();
    const notificacoes = useMemo(() => query.data?.pages.flatMap((p) => p.notificacoes) ?? [], [query.data]);
    const naoLidas = query.data?.pages[0]?.meta.nao_lidas ?? 0;

    useErroRede(query.isError && !query.data ? query.error : null, 'notificacoes');

    const abrir = (n: Notificacao) => {
        if (!n.lida) marcarLida.mutate(n.id);

        const rota = rotaDaNotificacao(n.rota_app);

        if (rota) router.navigate(rota);
    };

    return (
        <View style={{ flex: 1, backgroundColor: colors.background }}>
            <GradientHeader
                title="Notificações"
                subtitle={naoLidas > 0 ? `${naoLidas} não ${naoLidas === 1 ? 'lida' : 'lidas'}` : 'Tudo em dia'}
                overlap={8}
                right={
                    <Pressable
                        accessibilityRole="button"
                        accessibilityLabel="Voltar"
                        onPress={() => router.back()}
                        hitSlop={8}
                    >
                        <Ionicons name="close" size={26} color="#ffffff" />
                    </Pressable>
                }
            />

            {naoLidas > 0 ? (
                <Pressable
                    accessibilityRole="button"
                    disabled={marcarTodas.isPending}
                    onPress={() => marcarTodas.mutate()}
                    style={[styles.todas, { borderColor: colors.border, backgroundColor: colors.card }]}
                >
                    {marcarTodas.isPending ? (
                        <ActivityIndicator size="small" color={colors.primary} />
                    ) : (
                        <Ionicons name="checkmark-done-outline" size={18} color={colors.primary} />
                    )}
                    <Text style={{ color: colors.primary, fontWeight: '700', fontSize: 13 }}>
                        Marcar todas como lidas
                    </Text>
                </Pressable>
            ) : null}

            {query.isPending ? (
                <ActivityIndicator color={colors.primary} style={{ marginTop: 32 }} />
            ) : (
                <FlashList
                    data={notificacoes}
                    keyExtractor={(n) => n.id}
                    contentContainerStyle={{ paddingHorizontal: 14, paddingTop: 12, paddingBottom: 24 }}
                    ItemSeparatorComponent={() => <View style={{ height: 8 }} />}
                    renderItem={({ item }) => <ItemNotificacao notificacao={item} onPress={() => abrir(item)} />}
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
                            <EmptyState title="Nenhuma notificação" message="Avisos sobre o inventário aparecerão aqui." />
                        )
                    }
                    ListFooterComponent={
                        query.isFetchingNextPage ? (
                            <ActivityIndicator color={colors.primary} style={{ margin: 16 }} />
                        ) : query.isError && notificacoes.length > 0 ? (
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
    item: {
        flexDirection: 'row',
        gap: 10,
        padding: 14,
        borderRadius: radius.card,
        borderWidth: StyleSheet.hairlineWidth,
    },
    marcador: { width: 8, height: 8, borderRadius: 4, marginTop: 6 },
    textos: { flex: 1, gap: 4 },
    linha: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: 8 },
    titulo: { fontSize: 14, flex: 1 },
    corpo: { fontSize: 13, lineHeight: 19 },
    todas: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
        gap: 8,
        marginHorizontal: 14,
        marginTop: 12,
        paddingVertical: 10,
        borderRadius: radius.pill,
        borderWidth: StyleSheet.hairlineWidth,
    },
});
