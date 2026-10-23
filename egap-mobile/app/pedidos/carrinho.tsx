import { Ionicons } from '@expo/vector-icons';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useMemo, useState } from 'react';
import { ActivityIndicator, Pressable, ScrollView, StyleSheet, View } from 'react-native';

import { Text, TextInput } from '@/src/components/Text';
import { ApiError } from '@/src/api/client';
import type { PedidoItemPayload, PedidoPayload, PedidoTipo } from '@/src/api/types';
import { QUANTIDADE_MAX, useCarrinho, type ItemCarrinho } from '@/src/cart/CartProvider';
import { Card } from '@/src/components/Card';
import { EmptyState } from '@/src/components/EmptyState';
import { Folha } from '@/src/components/Folha';
import { GradientHeader } from '@/src/components/GradientHeader';
import { useContextoPedido, useEnviarPedido } from '@/src/hooks/usePedidos';
import { useTheme } from '@/src/theme/ThemeProvider';
import { radius } from '@/src/theme/tokens';
import { numero } from '@/src/utils/format';

type Erros = Record<string, string>;

interface FalhaEnvio {
    /** Rede/5xx: o carrinho foi mantido e o envio pode ser repetido. */
    rede: boolean;
    mensagem: string;
}

/** Valida como o servidor (CriarPedidoRequest + PedidosMobileService). */
function validar(
    tipo: PedidoTipo,
    complementoId: number | null,
    justificativa: string,
    itens: ItemCarrinho[],
): Erros {
    const erros: Erros = {};

    if (complementoId === null) erros.complemento_setor_id = 'Selecione o complemento do setor.';
    if (tipo === 'consumo' && justificativa.trim() === '') erros.justificativa = 'Informe a justificativa do pedido.';

    itens.forEach((item, i) => {
        if (tipo !== 'permanente') return;

        if (item.justificativa.trim() === '') {
            erros[`itens.${i}.justificativa`] = 'Informe a justificativa do material.';
        }
        if (item.tipoAtendimento === 'substituicao' && item.patrimonioSubstituido.trim() === '') {
            erros[`itens.${i}.patrimonio_substituido`] = 'Informe o patrimônio que será substituído.';
        }
    });

    return erros;
}

function Erro({ texto }: { texto?: string }) {
    const { colors } = useTheme();

    return texto ? <Text style={{ color: colors.danger, fontSize: 12 }}>{texto}</Text> : null;
}

export default function Carrinho() {
    const { colors } = useTheme();
    const router = useRouter();
    const params = useLocalSearchParams<{ tipo?: string }>();
    const tipo: PedidoTipo = params.tipo === 'permanente' ? 'permanente' : 'consumo';
    const { carrinho, atualizarItem, remover, definir, limpar } = useCarrinho();
    const { itens, complementoId, justificativa } = carrinho(tipo);
    const contexto = useContextoPedido();
    const enviar = useEnviarPedido();

    const [erros, setErros] = useState<Erros>({});
    const [falha, setFalha] = useState<FalhaEnvio | null>(null);
    const [escolhendo, setEscolhendo] = useState(false);
    const [filtro, setFiltro] = useState('');

    const complementos = useMemo(() => contexto.data?.complementos ?? [], [contexto.data]);
    const complemento = complementos.find((c) => c.id === complementoId);
    const filtrados = useMemo(() => {
        const f = filtro.trim().toLowerCase();

        return (f ? complementos.filter((c) => c.descricao.toLowerCase().includes(f)) : complementos).slice(0, 60);
    }, [complementos, filtro]);

    const borda = (chave: string) => (erros[chave] ? colors.danger : colors.border);

    const submeter = () => {
        if (enviar.isPending) return;

        const encontrados = validar(tipo, complementoId, justificativa, itens);

        setErros(encontrados);
        setFalha(null);

        if (Object.keys(encontrados).length > 0 || complementoId === null) return;

        const payload: PedidoPayload = {
            tipo,
            complemento_setor_id: complementoId,
            itens: itens.map((item): PedidoItemPayload => {
                if (tipo === 'consumo') {
                    return { material_id: item.materialId, quantidade: item.quantidade };
                }

                return {
                    material_id: item.materialId,
                    quantidade: item.quantidade,
                    tipo_atendimento: item.tipoAtendimento,
                    justificativa: item.justificativa.trim(),
                    ...(item.tipoAtendimento === 'substituicao'
                        ? { patrimonio_substituido: item.patrimonioSubstituido.trim() }
                        : {}),
                };
            }),
            ...(tipo === 'consumo' ? { justificativa: justificativa.trim() } : {}),
        };

        enviar.mutate(payload, {
            onSuccess: () => {
                limpar(tipo);
                router.dismissTo('/(tabs)/pedidos');
            },
            onError: (e) => {
                // Em qualquer falha o carrinho permanece intacto.
                if (e instanceof ApiError && e.status === 422) {
                    const porCampo: Erros = {};
                    const gerais: string[] = [];

                    for (const [campo, mensagens] of Object.entries(e.errors ?? {})) {
                        const texto = mensagens.join(' ');

                        if (campo === 'complemento_setor_id' || campo === 'justificativa' || campo.startsWith('itens.')) {
                            porCampo[campo] = texto;
                        } else {
                            gerais.push(texto);
                        }
                    }

                    setErros(porCampo);

                    // Erro de campo que a tela não exibe (ex.: itens.N, justificativa em consumo) não pode ficar mudo.
                    const exibidos = Object.keys(porCampo).filter(
                        (k) =>
                            k === 'complemento_setor_id' ||
                            (k === 'justificativa' && tipo === 'consumo') ||
                            (/^itens\.\d+\.(quantidade|material_id)$/.test(k) && Number(k.split('.')[1]) < itens.length) ||
                            (tipo === 'permanente' &&
                                /^itens\.\d+\.(tipo_atendimento|patrimonio_substituido|justificativa)$/.test(k) &&
                                Number(k.split('.')[1]) < itens.length),
                    );

                    if (gerais.length === 0 && exibidos.length === 0) {
                        gerais.push(...Object.values(porCampo), e.message);
                    }

                    setFalha(gerais.length > 0 ? { rede: false, mensagem: [...new Set(gerais)].join(' ') } : null);
                    return;
                }

                if (e instanceof ApiError && (e.status === 0 || e.status >= 500)) {
                    setFalha({
                        rede: true,
                        mensagem:
                            e.status === 0
                                ? 'Sem conexão com o servidor. Seu carrinho foi mantido; tente enviar novamente.'
                                : 'O servidor não conseguiu processar o pedido. Seu carrinho foi mantido; tente novamente.',
                    });
                    return;
                }

                setFalha({ rede: false, mensagem: e instanceof ApiError ? e.message : 'Não foi possível enviar o pedido.' });
            },
        });
    };

    const cabecalho = (
        <GradientHeader
            title={tipo === 'consumo' ? 'Carrinho de consumo' : 'Carrinho de permanente'}
            subtitle="Revisar e enviar"
            overlap={8}
            right={
                <Pressable accessibilityRole="button" accessibilityLabel="Voltar" onPress={() => router.back()} hitSlop={10}>
                    <Ionicons name="close" size={26} color="#ffffff" />
                </Pressable>
            }
        />
    );

    if (itens.length === 0) {
        return (
            <View style={{ flex: 1, backgroundColor: colors.background }}>
                {cabecalho}
                <EmptyState
                    title="Carrinho vazio"
                    message="Adicione materiais para montar o pedido."
                    actionLabel="Escolher materiais"
                    onAction={() => router.back()}
                />
            </View>
        );
    }

    return (
        <View style={{ flex: 1, backgroundColor: colors.background }}>
            {cabecalho}
            <ScrollView contentContainerStyle={styles.corpo} keyboardShouldPersistTaps="handled">
                <Card style={{ gap: 6 }}>
                    <Text style={[styles.rotulo, { color: colors.textMuted }]}>Complemento do setor</Text>
                    {contexto.isPending ? (
                        <ActivityIndicator color={colors.primary} />
                    ) : contexto.isError ? (
                        <Text onPress={() => void contexto.refetch()} style={{ color: colors.danger }}>
                            Não foi possível carregar os complementos. Toque para tentar novamente.
                        </Text>
                    ) : (
                        <Pressable
                            accessibilityRole="button"
                            onPress={() => setEscolhendo(true)}
                            style={[styles.campo, styles.seletor, { borderColor: borda('complemento_setor_id') }]}
                        >
                            <Text style={{ color: complemento ? colors.text : colors.textMuted, flex: 1 }}>
                                {complemento?.descricao ?? 'Selecionar…'}
                            </Text>
                            <Ionicons name="chevron-down" size={18} color={colors.textMuted} />
                        </Pressable>
                    )}
                    <Erro texto={erros.complemento_setor_id} />
                </Card>

                {tipo === 'consumo' ? (
                    <Card style={{ gap: 6 }}>
                        <Text style={[styles.rotulo, { color: colors.textMuted }]}>Justificativa</Text>
                        <TextInput
                            value={justificativa}
                            onChangeText={(v) => definir(tipo, { justificativa: v })}
                            multiline
                            maxLength={300}
                            placeholder="Por que este pedido é necessário?"
                            placeholderTextColor={colors.textMuted}
                            style={[styles.campo, styles.multilinha, { color: colors.text, borderColor: borda('justificativa') }]}
                        />
                        <Erro texto={erros.justificativa} />
                    </Card>
                ) : null}

                {itens.map((item, i) => (
                    <Card key={item.materialId} style={{ gap: 8 }}>
                        <View style={styles.linha}>
                            <Text style={{ color: colors.text, fontWeight: '700', flex: 1 }}>{item.descricao}</Text>
                            <Pressable
                                accessibilityRole="button"
                                accessibilityLabel="Remover item"
                                hitSlop={10}
                                onPress={() => remover(tipo, item.materialId)}
                            >
                                <Ionicons name="trash-outline" size={20} color={colors.danger} />
                            </Pressable>
                        </View>
                        <View style={styles.linha}>
                            <Text style={{ color: colors.textMuted, fontSize: 12, flex: 1 }}>{item.unidade}</Text>
                            <Pressable
                                accessibilityRole="button"
                                accessibilityLabel="Diminuir"
                                disabled={item.quantidade <= 1}
                                onPress={() => atualizarItem(tipo, item.materialId, { quantidade: item.quantidade - 1 })}
                                style={[styles.passo, { borderColor: colors.border, opacity: item.quantidade <= 1 ? 0.4 : 1 }]}
                            >
                                <Ionicons name="remove" size={18} color={colors.text} />
                            </Pressable>
                            <Text style={{ color: colors.text, fontWeight: '800', minWidth: 48, textAlign: 'center' }}>
                                {numero(item.quantidade)}
                            </Text>
                            <Pressable
                                accessibilityRole="button"
                                accessibilityLabel="Aumentar"
                                disabled={item.quantidade >= QUANTIDADE_MAX}
                                onPress={() => atualizarItem(tipo, item.materialId, { quantidade: item.quantidade + 1 })}
                                style={[
                                    styles.passo,
                                    { borderColor: colors.border, opacity: item.quantidade >= QUANTIDADE_MAX ? 0.4 : 1 },
                                ]}
                            >
                                <Ionicons name="add" size={18} color={colors.text} />
                            </Pressable>
                        </View>
                        <Erro texto={erros[`itens.${i}.quantidade`] ?? erros[`itens.${i}.material_id`]} />

                        {tipo === 'permanente' ? (
                            <>
                                <View style={styles.linha}>
                                    {(['adicao', 'substituicao'] as const).map((opcao) => {
                                        const ativo = item.tipoAtendimento === opcao;

                                        return (
                                            <Pressable
                                                key={opcao}
                                                accessibilityRole="button"
                                                onPress={() => atualizarItem(tipo, item.materialId, { tipoAtendimento: opcao })}
                                                style={[
                                                    styles.opcao,
                                                    {
                                                        backgroundColor: ativo ? colors.primary : 'transparent',
                                                        borderColor: ativo ? colors.primary : colors.border,
                                                    },
                                                ]}
                                            >
                                                <Text
                                                    style={{
                                                        color: ativo ? colors.onPrimary : colors.text,
                                                        fontWeight: '700',
                                                        fontSize: 13,
                                                    }}
                                                >
                                                    {opcao === 'adicao' ? 'Adição' : 'Substituição'}
                                                </Text>
                                            </Pressable>
                                        );
                                    })}
                                </View>
                                <Erro texto={erros[`itens.${i}.tipo_atendimento`]} />
                                {item.tipoAtendimento === 'substituicao' ? (
                                    <>
                                        <TextInput
                                            value={item.patrimonioSubstituido}
                                            onChangeText={(v) => atualizarItem(tipo, item.materialId, { patrimonioSubstituido: v })}
                                            maxLength={120}
                                            autoCapitalize="none"
                                            autoCorrect={false}
                                            placeholder="Patrimônio a ser substituído"
                                            placeholderTextColor={colors.textMuted}
                                            style={[
                                                styles.campo,
                                                { color: colors.text, borderColor: borda(`itens.${i}.patrimonio_substituido`) },
                                            ]}
                                        />
                                        <Erro texto={erros[`itens.${i}.patrimonio_substituido`]} />
                                    </>
                                ) : null}
                                <TextInput
                                    value={item.justificativa}
                                    onChangeText={(v) => atualizarItem(tipo, item.materialId, { justificativa: v })}
                                    multiline
                                    maxLength={300}
                                    placeholder="Justificativa do item"
                                    placeholderTextColor={colors.textMuted}
                                    style={[
                                        styles.campo,
                                        styles.multilinha,
                                        { color: colors.text, borderColor: borda(`itens.${i}.justificativa`) },
                                    ]}
                                />
                                <Erro texto={erros[`itens.${i}.justificativa`]} />
                            </>
                        ) : null}
                    </Card>
                ))}

                {falha ? (
                    <View style={[styles.aviso, { backgroundColor: `${colors.danger}1f`, borderColor: colors.danger }]}>
                        <Ionicons name={falha.rede ? 'cloud-offline-outline' : 'alert-circle-outline'} size={20} color={colors.danger} />
                        <Text style={{ color: colors.text, flex: 1, fontSize: 13 }}>{falha.mensagem}</Text>
                    </View>
                ) : null}

                <Pressable
                    accessibilityRole="button"
                    disabled={enviar.isPending}
                    onPress={submeter}
                    style={[styles.enviar, { backgroundColor: colors.primary, opacity: enviar.isPending ? 0.6 : 1 }]}
                >
                    {enviar.isPending ? (
                        <ActivityIndicator color={colors.onPrimary} />
                    ) : (
                        <Text style={{ color: colors.onPrimary, fontWeight: '800', fontSize: 15 }}>
                            {falha?.rede ? 'Tentar novamente' : 'Enviar pedido'}
                        </Text>
                    )}
                </Pressable>
            </ScrollView>

            <Folha visible={escolhendo} title="Complemento do setor" onClose={() => setEscolhendo(false)}>
                <TextInput
                    value={filtro}
                    onChangeText={setFiltro}
                    placeholder="Filtrar…"
                    placeholderTextColor={colors.textMuted}
                    autoCapitalize="none"
                    autoCorrect={false}
                    style={[styles.campo, { color: colors.text, borderColor: colors.border }]}
                />
                {filtrados.length === 0 ? (
                    <Text style={{ color: colors.textMuted }}>Nenhum complemento encontrado.</Text>
                ) : (
                    filtrados.map((c) => (
                        <Pressable
                            key={c.id}
                            accessibilityRole="button"
                            onPress={() => {
                                definir(tipo, { complementoId: c.id });
                                setErros((atual) => ({ ...atual, complemento_setor_id: '' }));
                                setEscolhendo(false);
                            }}
                            style={[styles.opcaoLista, { borderColor: colors.border }]}
                        >
                            <Text style={{ color: colors.text, flex: 1, fontWeight: c.id === complementoId ? '800' : '400' }}>
                                {c.descricao}
                            </Text>
                            {c.id === complementoId ? <Ionicons name="checkmark" size={18} color={colors.primary} /> : null}
                        </Pressable>
                    ))
                )}
            </Folha>
        </View>
    );
}

const styles = StyleSheet.create({
    corpo: { padding: 14, gap: 12, paddingBottom: 40 },
    rotulo: { fontSize: 11, fontWeight: '700' },
    linha: { flexDirection: 'row', alignItems: 'center', gap: 8 },
    campo: { borderWidth: 1, borderRadius: radius.input, paddingHorizontal: 12, paddingVertical: 10, fontSize: 14 },
    seletor: { flexDirection: 'row', alignItems: 'center', gap: 8 },
    multilinha: { minHeight: 72, textAlignVertical: 'top' },
    passo: { width: 34, height: 34, borderRadius: 17, borderWidth: 1, alignItems: 'center', justifyContent: 'center' },
    opcao: { flex: 1, alignItems: 'center', paddingVertical: 8, borderRadius: radius.pill, borderWidth: 1 },
    opcaoLista: {
        flexDirection: 'row',
        alignItems: 'center',
        paddingVertical: 12,
        borderBottomWidth: StyleSheet.hairlineWidth,
    },
    aviso: { flexDirection: 'row', gap: 10, alignItems: 'center', padding: 12, borderRadius: 14, borderWidth: 1 },
    enviar: { alignItems: 'center', justifyContent: 'center', minHeight: 48, borderRadius: radius.pill },
});
