import { useState } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, View } from 'react-native';

import { Text, TextInput } from '@/src/components/Text';
import { mensagemErro, useDivergencia } from '@/src/hooks/useConferencia';
import { Folha } from '@/src/components/Folha';
import { useTheme } from '@/src/theme/ThemeProvider';
import { radius } from '@/src/theme/tokens';

const CAMPOS = ['Descrição', 'Marca', 'Modelo', 'Número de série', 'Estado de conservação', 'Localização'];

/** Mesmos valores do cadastro do bem (o "Finalizar levantamento" grava o que vier). */
const ESTADOS = ['ÓTIMO', 'BOM', 'REGULAR', 'RUIM'];

const DICAS: Record<string, string> = {
    Descrição: 'Descrição encontrada',
    Marca: 'Marca encontrada',
    Modelo: 'Modelo encontrado',
    'Número de série': 'Número de série encontrado',
    Localização: 'Onde o bem está (sala, andar...)',
};

interface Props {
    visible: boolean;
    onClose: () => void;
    /** Divergência de um bem cadastrado; sem ele, informa-se o código patrimonial. */
    bem?: { id: number; rotulo: string } | null;
    codigoInicial?: string;
    /** Campos já marcados ao abrir (ex.: "Localização" para bem de outro setor). */
    camposIniciais?: string[];
    observacaoInicial?: string;
    onRegistrado?: (mensagem: string) => void;
}

export function DivergenciaModal({
    visible,
    onClose,
    bem = null,
    codigoInicial = '',
    camposIniciais,
    observacaoInicial,
    onRegistrado,
}: Props) {
    const { colors } = useTheme();
    const mutation = useDivergencia();
    const [codigo, setCodigo] = useState(codigoInicial);
    const [campos, setCampos] = useState<string[]>([]);
    /** Valor encontrado por campo: vai para o item e é aplicado ao bem no "Finalizar levantamento". */
    const [valores, setValores] = useState<Record<string, string>>({});
    const [observacao, setObservacao] = useState('');
    const [erro, setErro] = useState<string | null>(null);

    // Ao abrir, recomeça com o código recebido (ajuste na renderização, sem efeito).
    const [abertoAntes, setAbertoAntes] = useState(visible);
    if (visible !== abertoAntes) {
        setAbertoAntes(visible);
        if (visible) {
            setCodigo(codigoInicial);
            setErro(null);
            if (camposIniciais) setCampos(camposIniciais);
            if (observacaoInicial) setObservacao(observacaoInicial);
        }
    }

    const alternar = (campo: string) =>
        setCampos((atuais) => (atuais.includes(campo) ? atuais.filter((c) => c !== campo) : [...atuais, campo]));

    const enviar = async () => {
        setErro(null);
        const preenchidos = Object.fromEntries(
            campos.map((c) => [c, (valores[c] ?? '').trim()] as const).filter(([, v]) => v !== ''),
        );

        try {
            const resposta = await mutation.mutateAsync({
                ...(bem ? { bem_id: bem.id } : { codigo: codigo.trim() }),
                ...(campos.length > 0 ? { campos } : {}),
                ...(Object.keys(preenchidos).length > 0 ? { valores: preenchidos } : {}),
                observacao: observacao.trim(),
            });

            setObservacao('');
            setCampos([]);
            setValores({});
            onRegistrado?.(resposta.message ?? 'Divergência registrada.');
            onClose();
        } catch (e) {
            setErro(mensagemErro(e, 'Não foi possível registrar a divergência.'));
        }
    };

    const campoTexto = [styles.input, { color: colors.text, backgroundColor: colors.card, borderColor: colors.border }];

    return (
        <Folha visible={visible} title="Registrar divergência" onClose={onClose}>
            {bem ? (
                <Text style={{ color: colors.textMuted }}>{bem.rotulo}</Text>
            ) : (
                <TextInput
                    value={codigo}
                    onChangeText={setCodigo}
                    placeholder="Código patrimonial"
                    placeholderTextColor={colors.textMuted}
                    autoCapitalize="characters"
                    autoCorrect={false}
                    style={campoTexto}
                />
            )}
            <Text style={{ color: colors.textMuted, fontSize: 13 }}>Campos divergentes (opcional)</Text>
            <View style={styles.chips}>
                {CAMPOS.map((campo) => {
                    const ativo = campos.includes(campo);

                    return (
                        <Pressable
                            key={campo}
                            accessibilityRole="checkbox"
                            accessibilityState={{ checked: ativo }}
                            onPress={() => alternar(campo)}
                            style={[
                                styles.chip,
                                {
                                    backgroundColor: ativo ? colors.primary : colors.card,
                                    borderColor: ativo ? colors.primary : colors.border,
                                },
                            ]}
                        >
                            <Text style={{ color: ativo ? colors.onPrimary : colors.text, fontWeight: '600' }}>{campo}</Text>
                        </Pressable>
                    );
                })}
            </View>
            {campos.map((campo) =>
                campo === 'Estado de conservação' ? (
                    <View key={campo} style={{ gap: 6 }}>
                        <Text style={{ color: colors.textMuted, fontSize: 13 }}>Estado encontrado</Text>
                        <View style={styles.chips}>
                            {ESTADOS.map((estado) => {
                                const ativo = valores[campo] === estado;

                                return (
                                    <Pressable
                                        key={estado}
                                        accessibilityRole="radio"
                                        accessibilityState={{ checked: ativo }}
                                        onPress={() => setValores((v) => ({ ...v, [campo]: estado }))}
                                        style={[
                                            styles.chip,
                                            {
                                                backgroundColor: ativo ? colors.primary : colors.card,
                                                borderColor: ativo ? colors.primary : colors.border,
                                            },
                                        ]}
                                    >
                                        <Text style={{ color: ativo ? colors.onPrimary : colors.text, fontWeight: '600' }}>
                                            {estado}
                                        </Text>
                                    </Pressable>
                                );
                            })}
                        </View>
                    </View>
                ) : (
                    <TextInput
                        key={campo}
                        value={valores[campo] ?? ''}
                        onChangeText={(texto) => setValores((v) => ({ ...v, [campo]: texto }))}
                        placeholder={`${DICAS[campo] ?? campo} (opcional)`}
                        placeholderTextColor={colors.textMuted}
                        maxLength={255}
                        style={campoTexto}
                    />
                ),
            )}
            <TextInput
                value={observacao}
                onChangeText={setObservacao}
                placeholder="Observação (obrigatória)"
                placeholderTextColor={colors.textMuted}
                multiline
                maxLength={255}
                style={[campoTexto, { minHeight: 90, textAlignVertical: 'top' }]}
            />
            {erro ? (
                <Text accessibilityRole="alert" style={{ color: colors.danger, fontWeight: '600' }}>
                    {erro}
                </Text>
            ) : null}
            <Pressable
                accessibilityRole="button"
                disabled={mutation.isPending}
                onPress={() => void enviar()}
                style={[styles.botao, { backgroundColor: colors.primary, opacity: mutation.isPending ? 0.6 : 1 }]}
            >
                {mutation.isPending ? (
                    <ActivityIndicator color={colors.onPrimary} />
                ) : (
                    <Text style={{ color: colors.onPrimary, fontWeight: '700' }}>Registrar</Text>
                )}
            </Pressable>
        </Folha>
    );
}

const styles = StyleSheet.create({
    input: { borderWidth: 1, borderRadius: radius.input, paddingHorizontal: 14, paddingVertical: 12, fontSize: 15 },
    chips: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
    chip: { alignSelf: 'flex-start', borderWidth: 1, borderRadius: radius.pill, paddingHorizontal: 14, paddingVertical: 7 },
    botao: { alignItems: 'center', justifyContent: 'center', paddingVertical: 14, borderRadius: radius.input },
});
