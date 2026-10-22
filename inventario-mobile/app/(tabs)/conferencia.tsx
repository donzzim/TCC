import { Ionicons } from '@expo/vector-icons';
import { FlashList } from '@shopify/flash-list';
import { useRouter } from 'expo-router';
import { useEffect, useMemo, useState } from 'react';
import { ActivityIndicator, Pressable, ScrollView, StyleSheet, Text, TextInput, View } from 'react-native';

import type { BemConferencia, ConferenciaFiltro } from '@/src/api/types';
import { Card } from '@/src/components/Card';
import { DivergenciaModal } from '@/src/components/DivergenciaModal';
import { EmptyState } from '@/src/components/EmptyState';
import { Folha } from '@/src/components/Folha';
import { GradientHeader } from '@/src/components/GradientHeader';
import { ProgressRing } from '@/src/components/ProgressRing';
import { StatPill } from '@/src/components/StatPill';
import {
    mensagemErro,
    useConferenciaAtual,
    useConferenciaBens,
    useFinalizarSetor,
    useNaoLocalizados,
} from '@/src/hooks/useConferencia';
import { useErroRede } from '@/src/hooks/useErroRede';
import { useTheme } from '@/src/theme/ThemeProvider';
import { radius } from '@/src/theme/tokens';
import { numero } from '@/src/utils/format';

const FILTROS: { valor: ConferenciaFiltro; rotulo: string }[] = [
    { valor: 'todos', rotulo: 'Todos' },
    { valor: 'pendente', rotulo: 'Pendentes' },
    { valor: 'localizado', rotulo: 'Localizados' },
    { valor: 'nao_localizado', rotulo: 'Não localizados' },
    { valor: 'divergente', rotulo: 'Divergentes' },
    { valor: 'em_transferencia', rotulo: 'Em transferência' },
    { valor: 'cadastrado_manualmente', rotulo: 'Cadastrados manualmente' },
];

export default function Conferencia() {
    const { colors } = useTheme();
    const router = useRouter();
    const [filtro, setFiltro] = useState<ConferenciaFiltro>('pendente');
    const [selecionados, setSelecionados] = useState<number[]>([]);
    const [aviso, setAviso] = useState<string | null>(null);
    const [justificando, setJustificando] = useState(false);
    const [justificativa, setJustificativa] = useState('');
    const [erroJustificativa, setErroJustificativa] = useState<string | null>(null);
    const [confirmandoFim, setConfirmandoFim] = useState(false);
    const [erroFim, setErroFim] = useState<string | null>(null);
    const [divergencia, setDivergencia] = useState<{ aberta: boolean; bem: BemConferencia | null }>({
        aberta: false,
        bem: null,
    });

    const atual = useConferenciaAtual();
    const lista = useConferenciaBens(filtro);
    const naoLocalizados = useNaoLocalizados();
    const finalizar = useFinalizarSetor();

    useErroRede(atual.isError && !atual.data ? atual.error : null, 'conferencia');

    const itens = useMemo(() => lista.data?.pages.flatMap((p) => p.bens) ?? [], [lista.data]);
    const resumo = atual.data?.resumo;
    const atividade = atual.data?.atividade;
    const editavel = !!atividade?.pode_editar;
    const percentual = resumo && resumo.total > 0 ? ((resumo.total - resumo.pendentes) / resumo.total) * 100 : 0;

    // A seleção vale só para o filtro atual e só para bens ainda pendentes.
    useEffect(() => setSelecionados([]), [filtro]);
    useEffect(() => {
        const pendentes = new Set(itens.filter((b) => b.conferencia.status === 'pendente').map((b) => b.id));

        setSelecionados((atuais) => {
            const restantes = atuais.filter((id) => pendentes.has(id));

            return restantes.length === atuais.length ? atuais : restantes;
        });
    }, [itens]);

    const alternar = (id: number) =>
        setSelecionados((atuais) => (atuais.includes(id) ? atuais.filter((x) => x !== id) : [...atuais, id]));

    const marcarNaoLocalizados = async () => {
        setErroJustificativa(null);
        try {
            const r = await naoLocalizados.mutateAsync({ bemIds: selecionados, justificativa: justificativa.trim() });

            setAviso(r.message ?? 'Bens marcados como não localizados.');
            setSelecionados([]);
            setJustificativa('');
            setJustificando(false);
        } catch (e) {
            setErroJustificativa(mensagemErro(e, 'Não foi possível marcar os bens.'));
        }
    };

    const finalizarSetor = async () => {
        setErroFim(null);
        try {
            const r = await finalizar.mutateAsync();

            setAviso(r.message ?? 'Conferência do setor finalizada.');
            setConfirmandoFim(false);
        } catch (e) {
            setErroFim(mensagemErro(e, 'Não foi possível finalizar o setor.'));
        }
    };

    const cabecalho = (
        <View style={styles.cabecalho}>
            <Card style={styles.resumoCard}>
                {resumo ? (
                    <View style={styles.pills}>
                        <StatPill label="pendentes" value={numero(resumo.pendentes)} tone="warning" />
                        <StatPill label="localizados" value={numero(resumo.localizados)} tone="success" />
                        <StatPill label="não localizados" value={numero(resumo.nao_localizados)} tone="danger" />
                        <StatPill label="divergentes" value={numero(resumo.divergentes)} />
                    </View>
                ) : (
                    <ActivityIndicator color={colors.primary} />
                )}
            </Card>

            {atual.data && !editavel ? (
                <Text style={[styles.aviso, { color: colors.warning }]}>
                    A conferência deste setor não pode ser editada ({atividade?.situacao ?? 'encerrada'}).
                </Text>
            ) : null}
            {aviso ? (
                <Text accessibilityRole="alert" style={[styles.aviso, { color: colors.success }]}>
                    {aviso}
                </Text>
            ) : null}

            <View style={styles.acoes}>
                <Acao
                    icone="barcode-outline"
                    rotulo="Ler código"
                    desabilitado={!editavel}
                    onPress={() => router.push({ pathname: '/leitura', params: { modo: 'conferencia' } })}
                />
                <Acao
                    icone="alert-circle-outline"
                    rotulo="Divergência"
                    desabilitado={!editavel}
                    onPress={() => setDivergencia({ aberta: true, bem: null })}
                />
                <Acao
                    icone="checkmark-done-outline"
                    rotulo="Finalizar setor"
                    desabilitado={!editavel || !atividade?.pode_finalizar}
                    onPress={() => {
                        setErroFim(null);
                        setConfirmandoFim(true);
                    }}
                />
            </View>

            {selecionados.length > 0 ? (
                <Pressable
                    accessibilityRole="button"
                    disabled={!editavel}
                    onPress={() => {
                        setErroJustificativa(null);
                        setJustificando(true);
                    }}
                    style={[styles.botaoSelecao, { backgroundColor: colors.danger, opacity: editavel ? 1 : 0.5 }]}
                >
                    <Text style={{ color: '#ffffff', fontWeight: '700' }}>
                        Marcar não localizados ({selecionados.length})
                    </Text>
                </Pressable>
            ) : null}

            <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chips}>
                {FILTROS.map((f) => {
                    const ativo = f.valor === filtro;

                    return (
                        <Pressable
                            key={f.valor}
                            accessibilityRole="button"
                            accessibilityState={{ selected: ativo }}
                            onPress={() => setFiltro(f.valor)}
                            style={[
                                styles.chip,
                                {
                                    backgroundColor: ativo ? colors.primary : colors.card,
                                    borderColor: ativo ? colors.primary : colors.border,
                                },
                            ]}
                        >
                            <Text style={{ color: ativo ? colors.onPrimary : colors.text, fontWeight: '600', fontSize: 13 }}>
                                {f.rotulo}
                            </Text>
                        </Pressable>
                    );
                })}
            </ScrollView>
        </View>
    );

    return (
        <View style={{ flex: 1, backgroundColor: colors.background }}>
            <GradientHeader
                title="Conferência"
                subtitle={
                    atual.data
                        ? `Inventário ${atual.data.inventario.numero ?? ''}/${atual.data.inventario.ano ?? ''} · ${atividade?.situacao ?? ''}`
                        : undefined
                }
                overlap={28}
                right={<ProgressRing percent={percentual} size={58} />}
            />
            <View style={styles.sobrepor}>{cabecalho}</View>

            {lista.isPending ? (
                <ActivityIndicator color={colors.primary} style={{ marginTop: 24 }} />
            ) : (
                <FlashList
                    data={itens}
                    extraData={selecionados}
                    keyExtractor={(b) => String(b.id)}
                    contentContainerStyle={{ paddingHorizontal: 14, paddingBottom: 24 }}
                    ItemSeparatorComponent={() => <View style={{ height: 8 }} />}
                    renderItem={({ item }) => (
                        <LinhaConferencia
                            bem={item}
                            editavel={editavel}
                            selecionado={selecionados.includes(item.id)}
                            onSelecionar={() => alternar(item.id)}
                            onAbrir={() =>
                                router.push({ pathname: '/bem/[patrimonio]', params: { patrimonio: String(item.patrimonio) } })
                            }
                            onDivergencia={() => setDivergencia({ aberta: true, bem: item })}
                        />
                    )}
                    onEndReachedThreshold={0.5}
                    onEndReached={() => {
                        if (lista.hasNextPage && !lista.isFetchingNextPage) {
                            void lista.fetchNextPage();
                        }
                    }}
                    refreshing={lista.isRefetching && !lista.isFetchingNextPage}
                    onRefresh={() => {
                        void lista.refetch();
                        void atual.refetch();
                    }}
                    ListEmptyComponent={
                        lista.isError ? (
                            <EmptyState
                                title="Não foi possível carregar"
                                message={lista.error.message}
                                actionLabel="Tentar novamente"
                                onAction={() => void lista.refetch()}
                            />
                        ) : (
                            <EmptyState title="Nenhum bem neste filtro" />
                        )
                    }
                    ListFooterComponent={
                        lista.isFetchingNextPage ? <ActivityIndicator color={colors.primary} style={{ margin: 16 }} /> : null
                    }
                />
            )}

            <Folha visible={justificando} title="Marcar como não localizados" onClose={() => setJustificando(false)}>
                <Text style={{ color: colors.textMuted }}>
                    {selecionados.length} bem(ns) selecionado(s). Informe a justificativa.
                </Text>
                <TextInput
                    value={justificativa}
                    onChangeText={setJustificativa}
                    placeholder="Justificativa (obrigatória)"
                    placeholderTextColor={colors.textMuted}
                    multiline
                    style={[styles.input, { color: colors.text, backgroundColor: colors.card, borderColor: colors.border }]}
                />
                {erroJustificativa ? (
                    <Text accessibilityRole="alert" style={{ color: colors.danger, fontWeight: '600' }}>
                        {erroJustificativa}
                    </Text>
                ) : null}
                <BotaoFolha
                    rotulo="Confirmar"
                    cor={colors.danger}
                    carregando={naoLocalizados.isPending}
                    onPress={() => void marcarNaoLocalizados()}
                />
            </Folha>

            <Folha visible={confirmandoFim} title="Finalizar setor" onClose={() => setConfirmandoFim(false)}>
                <Text style={{ color: colors.text }}>
                    Deseja finalizar a conferência deste setor? Depois disso não será possível editar.
                </Text>
                {erroFim ? (
                    <Text accessibilityRole="alert" style={{ color: colors.danger, fontWeight: '600' }}>
                        {erroFim}
                    </Text>
                ) : null}
                <BotaoFolha
                    rotulo="Finalizar"
                    cor={colors.primary}
                    carregando={finalizar.isPending}
                    onPress={() => void finalizarSetor()}
                />
            </Folha>

            <DivergenciaModal
                visible={divergencia.aberta}
                onClose={() => setDivergencia((d) => ({ ...d, aberta: false }))}
                bem={
                    divergencia.bem
                        ? {
                              id: divergencia.bem.id,
                              rotulo: `${divergencia.bem.descricao_resumida || divergencia.bem.descricao || 'Bem'} · Nº ${divergencia.bem.patrimonio ?? '-'}`,
                          }
                        : null
                }
                onRegistrado={setAviso}
            />
        </View>
    );
}

function Acao({
    icone,
    rotulo,
    desabilitado,
    onPress,
}: {
    icone: keyof typeof Ionicons.glyphMap;
    rotulo: string;
    desabilitado: boolean;
    onPress: () => void;
}) {
    const { colors } = useTheme();

    return (
        <Pressable
            accessibilityRole="button"
            accessibilityState={{ disabled: desabilitado }}
            disabled={desabilitado}
            onPress={onPress}
            style={[
                styles.acao,
                { backgroundColor: colors.card, borderColor: colors.border, opacity: desabilitado ? 0.45 : 1 },
            ]}
        >
            <Ionicons name={icone} size={20} color={colors.primary} />
            <Text style={{ color: colors.text, fontSize: 12, fontWeight: '700' }}>{rotulo}</Text>
        </Pressable>
    );
}

function BotaoFolha({
    rotulo,
    cor,
    carregando,
    onPress,
}: {
    rotulo: string;
    cor: string;
    carregando: boolean;
    onPress: () => void;
}) {
    return (
        <Pressable
            accessibilityRole="button"
            disabled={carregando}
            onPress={onPress}
            style={[styles.botaoFolha, { backgroundColor: cor, opacity: carregando ? 0.6 : 1 }]}
        >
            {carregando ? (
                <ActivityIndicator color="#ffffff" />
            ) : (
                <Text style={{ color: '#ffffff', fontWeight: '700' }}>{rotulo}</Text>
            )}
        </Pressable>
    );
}

function LinhaConferencia({
    bem,
    editavel,
    selecionado,
    onSelecionar,
    onAbrir,
    onDivergencia,
}: {
    bem: BemConferencia;
    editavel: boolean;
    selecionado: boolean;
    onSelecionar: () => void;
    onAbrir: () => void;
    onDivergencia: () => void;
}) {
    const { colors } = useTheme();
    const status = bem.conferencia.status;
    const selecionavel = status === 'pendente' && editavel;
    const cor =
        status === 'localizado'
            ? colors.success
            : status === 'nao_localizado'
              ? colors.danger
              : status === 'pendente'
                ? colors.textMuted
                : colors.warning;

    return (
        <Pressable
            accessibilityRole="button"
            onPress={selecionavel ? onSelecionar : onAbrir}
            onLongPress={onAbrir}
            style={[styles.linha, { backgroundColor: colors.card, borderColor: selecionado ? colors.primary : colors.border }]}
        >
            {selecionavel ? (
                <Ionicons
                    name={selecionado ? 'checkbox' : 'square-outline'}
                    size={22}
                    color={selecionado ? colors.primary : colors.textMuted}
                />
            ) : null}
            <View style={{ flex: 1 }}>
                <Text style={[styles.titulo, { color: colors.text }]} numberOfLines={2}>
                    {bem.descricao_resumida || bem.descricao || 'Sem descrição'}
                </Text>
                <Text style={{ color: colors.textMuted, fontSize: 12, marginTop: 2 }}>Nº {bem.patrimonio ?? '-'}</Text>
            </View>
            <View style={[styles.pill, { backgroundColor: `${cor}1f` }]}>
                <Text style={{ color: cor, fontSize: 11, fontWeight: '700' }} numberOfLines={1}>
                    {bem.conferencia.status_label}
                </Text>
            </View>
            {editavel ? (
                <Pressable accessibilityRole="button" accessibilityLabel="Registrar divergência" hitSlop={8} onPress={onDivergencia}>
                    <Ionicons name="alert-circle-outline" size={22} color={colors.textMuted} />
                </Pressable>
            ) : null}
        </Pressable>
    );
}

const styles = StyleSheet.create({
    sobrepor: { marginTop: -22 },
    cabecalho: { paddingHorizontal: 14, gap: 10, paddingBottom: 10 },
    resumoCard: { padding: 12, minHeight: 56, justifyContent: 'center' },
    pills: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
    aviso: { fontSize: 13, fontWeight: '600' },
    acoes: { flexDirection: 'row', gap: 8 },
    acao: {
        flex: 1,
        alignItems: 'center',
        gap: 4,
        paddingVertical: 10,
        borderRadius: radius.input,
        borderWidth: StyleSheet.hairlineWidth,
    },
    botaoSelecao: { alignItems: 'center', paddingVertical: 12, borderRadius: radius.input },
    chips: { gap: 8, paddingVertical: 2 },
    chip: { borderWidth: 1, borderRadius: radius.pill, paddingHorizontal: 14, paddingVertical: 7 },
    linha: { flexDirection: 'row', alignItems: 'center', gap: 10, padding: 12, borderRadius: 14, borderWidth: 1 },
    titulo: { fontSize: 14, fontWeight: '600' },
    pill: { paddingHorizontal: 10, paddingVertical: 3, borderRadius: radius.pill, maxWidth: 120 },
    input: {
        borderWidth: 1,
        borderRadius: radius.input,
        paddingHorizontal: 14,
        paddingVertical: 12,
        fontSize: 15,
        minHeight: 90,
        textAlignVertical: 'top',
    },
    botaoFolha: { alignItems: 'center', justifyContent: 'center', paddingVertical: 14, borderRadius: radius.input },
});
