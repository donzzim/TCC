import { Ionicons } from '@expo/vector-icons';
import { useFocusEffect, useRouter } from 'expo-router';
import { useCallback, useState } from 'react';
import { ActivityIndicator, Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';

import { Card } from '@/src/components/Card';
import { GradientHeader } from '@/src/components/GradientHeader';
import { ProgressRing } from '@/src/components/ProgressRing';
import { SetorSheet } from '@/src/components/SetorSheet';
import { useDashboard } from '@/src/hooks/useDashboard';
import { useErroRede } from '@/src/hooks/useErroRede';
import { useSessao } from '@/src/session/SessionProvider';
import { useTheme } from '@/src/theme/ThemeProvider';
import { radius } from '@/src/theme/tokens';
import { moeda, numero } from '@/src/utils/format';
import { lerRecentes, type Recente } from '@/src/utils/recentes';

function saudacao(): string {
    const h = new Date().getHours();

    return h < 12 ? 'Bom dia,' : h < 18 ? 'Boa tarde,' : 'Boa noite,';
}

function iniciais(nome: string): string {
    return nome
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p[0]?.toUpperCase())
        .join('');
}

const STATUS_LABEL: Record<string, string> = {
    em_andamento: 'em andamento',
    aberta: 'em andamento',
    aberto: 'em andamento',
    finalizada: 'finalizada',
    finalizado: 'finalizada',
};

export default function Inicio() {
    const { colors } = useTheme();
    const router = useRouter();
    const { sessao } = useSessao();
    const dashboard = useDashboard();
    const [setorAberto, setSetorAberto] = useState(false);
    const [recentes, setRecentes] = useState<Recente[]>([]);
    const [atualizando, setAtualizando] = useState(false);

    useErroRede(dashboard.error, 'dashboard');

    useFocusEffect(
        useCallback(() => {
            void lerRecentes().then(setRecentes);
        }, []),
    );

    const atualizar = async () => {
        setAtualizando(true);
        await dashboard.refetch();
        setRecentes(await lerRecentes());
        setAtualizando(false);
    };

    const dados = dashboard.data;
    const conf = dados?.conferencia ?? null;
    const resumo = conf?.resumo;
    const percentual = resumo && resumo.total > 0 ? (resumo.localizados / resumo.total) * 100 : 0;
    const nome = sessao?.usuario.nome ?? '';
    const setor = sessao?.setor_atual;
    const situacaoConf = conf?.atividade.situacao ?? conf?.inventario.situacao ?? '';

    return (
        <ScrollView
            style={{ flex: 1, backgroundColor: colors.background }}
            contentContainerStyle={{ paddingBottom: 24 }}
            refreshControl={<RefreshControl refreshing={atualizando} onRefresh={() => void atualizar()} />}
        >
            <GradientHeader
                subtitle={saudacao()}
                title={nome || 'Início'}
                overlap={36}
                right={
                    <Pressable
                        accessibilityRole="button"
                        accessibilityLabel="Perfil"
                        onPress={() => router.push('/perfil')}
                        style={styles.avatar}
                    >
                        <Text style={styles.avatarTexto}>{iniciais(nome) || '?'}</Text>
                    </Pressable>
                }
            >
                <Pressable
                    accessibilityRole="button"
                    accessibilityLabel="Trocar setor"
                    onPress={() => setSetorAberto(true)}
                    style={styles.setorPill}
                >
                    <Ionicons name="business-outline" size={14} color="#ffffff" />
                    <Text style={styles.setorTexto} numberOfLines={1}>
                        {setor ? `${setor.setor} · ${setor.unidade}` : 'Sem setor'}
                    </Text>
                    <Ionicons name="chevron-down" size={14} color="#ffffff" />
                </Pressable>
            </GradientHeader>

            <View style={styles.corpo}>
                {dashboard.isPending && !dados ? (
                    <ActivityIndicator color={colors.primary} style={{ marginTop: 24 }} />
                ) : null}

                {dados ? (
                    <>
                        <Card style={styles.flutuante}>
                            {conf && resumo ? (
                                <>
                                    <View style={styles.linha}>
                                        <Text style={[styles.cardTitulo, { color: colors.text }]}>
                                            Conferência {conf.inventario.ano ?? ''}
                                        </Text>
                                        <View style={[styles.tag, { backgroundColor: `${colors.primary}1f` }]}>
                                            <Text style={[styles.tagTexto, { color: colors.primary }]}>
                                                {STATUS_LABEL[situacaoConf.toLowerCase()] ?? situacaoConf}
                                            </Text>
                                        </View>
                                    </View>
                                    <View style={[styles.linha, { marginTop: 12, justifyContent: 'flex-start', gap: 16 }]}>
                                        <ProgressRing percent={percentual} size={72} />
                                        <View style={{ gap: 3 }}>
                                            <Text style={{ color: colors.success, fontSize: 13 }}>
                                                ● {numero(resumo.localizados)} localizados
                                            </Text>
                                            <Text style={{ color: colors.warning, fontSize: 13 }}>
                                                ● {numero(resumo.divergentes)} divergentes
                                            </Text>
                                            <Text style={{ color: colors.danger, fontSize: 13 }}>
                                                ● {numero(resumo.nao_localizados)} não localizados
                                            </Text>
                                        </View>
                                    </View>
                                </>
                            ) : (
                                <>
                                    <Text style={[styles.cardTitulo, { color: colors.text }]}>Conferência</Text>
                                    <Text style={{ color: colors.textMuted, marginTop: 6, fontSize: 13 }}>
                                        Nenhuma conferência em andamento para este setor.
                                    </Text>
                                </>
                            )}
                        </Card>

                        <View style={styles.grade}>
                            <Card style={styles.metade}>
                                <Ionicons name="cube-outline" size={20} color={colors.primary} />
                                <Text style={[styles.numero, { color: colors.text }]}>{numero(dados.bens.total)}</Text>
                                <Text style={{ color: colors.textMuted, fontSize: 12 }}>bens</Text>
                            </Card>
                            <Card style={styles.metade}>
                                <Ionicons name="cart-outline" size={20} color={colors.primary} />
                                <Text style={[styles.numero, { color: colors.text }]}>
                                    {numero(dados.pedidos_abertos)}
                                </Text>
                                <Text style={{ color: colors.textMuted, fontSize: 12 }}>pedidos abertos</Text>
                            </Card>
                        </View>

                        <Card style={{ gap: 6 }}>
                            <View style={styles.linha}>
                                <Text style={{ color: colors.textMuted, fontSize: 13 }}>Valor de aquisição</Text>
                                <Text style={[styles.valor, { color: colors.text }]}>
                                    {moeda(Number(dados.financeiro.valor_aquisicao) || 0)}
                                </Text>
                            </View>
                            <View style={styles.linha}>
                                <Text style={{ color: colors.textMuted, fontSize: 13 }}>Valor atual</Text>
                                <Text style={[styles.valor, { color: colors.text }]}>
                                    {moeda(Number(dados.financeiro.valor_atual) || 0)}
                                </Text>
                            </View>
                            {dados.financeiro.sem_valor > 0 ? (
                                <Text style={{ color: colors.textMuted, fontSize: 11 }}>
                                    {numero(dados.financeiro.sem_valor)} bens sem valor cadastrado
                                </Text>
                            ) : null}
                        </Card>
                    </>
                ) : null}

                <Text style={[styles.secao, { color: colors.text }]}>Últimas leituras</Text>
                {recentes.length === 0 ? (
                    <Text style={{ color: colors.textMuted, fontSize: 13 }}>
                        Nenhuma leitura ainda. Toque no botão central para ler uma plaqueta.
                    </Text>
                ) : (
                    recentes.map((r) => (
                        <Pressable
                            key={r.patrimonio}
                            accessibilityRole="button"
                            onPress={() => router.push({ pathname: '/bem/[patrimonio]', params: { patrimonio: r.patrimonio } })}
                            style={[styles.recente, { backgroundColor: colors.card, borderColor: colors.border }]}
                        >
                            <View style={{ flex: 1 }}>
                                <Text style={{ color: colors.text, fontWeight: '600', fontSize: 14 }} numberOfLines={1}>
                                    {r.descricao || 'Sem descrição'}
                                </Text>
                                <Text style={{ color: colors.textMuted, fontSize: 12 }}>Nº {r.patrimonio}</Text>
                            </View>
                            <Ionicons name="chevron-forward" size={18} color={colors.textMuted} />
                        </Pressable>
                    ))
                )}
            </View>

            <SetorSheet visible={setorAberto} onClose={() => setSetorAberto(false)} />
        </ScrollView>
    );
}

const styles = StyleSheet.create({
    avatar: {
        width: 36,
        height: 36,
        borderRadius: 18,
        backgroundColor: 'rgba(255,255,255,0.2)',
        alignItems: 'center',
        justifyContent: 'center',
    },
    avatarTexto: { color: '#ffffff', fontWeight: '700', fontSize: 13 },
    setorPill: {
        marginTop: 10,
        alignSelf: 'flex-start',
        flexDirection: 'row',
        alignItems: 'center',
        gap: 6,
        backgroundColor: 'rgba(255,255,255,0.18)',
        borderRadius: radius.pill,
        paddingHorizontal: 12,
        paddingVertical: 6,
        maxWidth: '100%',
    },
    setorTexto: { color: '#ffffff', fontSize: 12, fontWeight: '600', flexShrink: 1 },
    corpo: { paddingHorizontal: 14, gap: 12 },
    flutuante: { marginTop: -36, shadowOpacity: 0.16 },
    linha: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
    cardTitulo: { fontSize: 14, fontWeight: '700' },
    tag: { paddingHorizontal: 10, paddingVertical: 3, borderRadius: radius.pill },
    tagTexto: { fontSize: 11, fontWeight: '700' },
    grade: { flexDirection: 'row', gap: 10 },
    metade: { flex: 1, gap: 2 },
    numero: { fontSize: 22, fontWeight: '800' },
    valor: { fontSize: 14, fontWeight: '700' },
    secao: { fontSize: 14, fontWeight: '700', marginTop: 4 },
    recente: {
        flexDirection: 'row',
        alignItems: 'center',
        padding: 12,
        borderRadius: 14,
        borderWidth: StyleSheet.hairlineWidth,
    },
});
