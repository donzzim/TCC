import { useEffect, useState } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, TextInput, View } from 'react-native';

import { mensagemErro, useDivergencia } from '@/src/hooks/useConferencia';
import { Folha } from '@/src/components/Folha';
import { useTheme } from '@/src/theme/ThemeProvider';
import { radius } from '@/src/theme/tokens';

const CAMPOS = ['Descrição', 'Marca', 'Modelo', 'Número de série', 'Estado de conservação', 'Localização'];

interface Props {
    visible: boolean;
    onClose: () => void;
    /** Divergência de um bem cadastrado; sem ele, informa-se o código patrimonial. */
    bem?: { id: number; rotulo: string } | null;
    codigoInicial?: string;
    onRegistrado?: (mensagem: string) => void;
}

export function DivergenciaModal({ visible, onClose, bem = null, codigoInicial = '', onRegistrado }: Props) {
    const { colors } = useTheme();
    const mutation = useDivergencia();
    const [codigo, setCodigo] = useState(codigoInicial);
    const [campos, setCampos] = useState<string[]>([]);
    const [observacao, setObservacao] = useState('');
    const [erro, setErro] = useState<string | null>(null);

    useEffect(() => {
        if (visible) {
            setCodigo(codigoInicial);
            setErro(null);
        }
    }, [visible, codigoInicial]);

    const alternar = (campo: string) =>
        setCampos((atuais) => (atuais.includes(campo) ? atuais.filter((c) => c !== campo) : [...atuais, campo]));

    const enviar = async () => {
        setErro(null);
        try {
            const resposta = await mutation.mutateAsync({
                ...(bem ? { bem_id: bem.id } : { codigo: codigo.trim() }),
                ...(campos.length > 0 ? { campos } : {}),
                observacao: observacao.trim(),
            });

            setObservacao('');
            setCampos([]);
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
            <TextInput
                value={observacao}
                onChangeText={setObservacao}
                placeholder="Observação (obrigatória)"
                placeholderTextColor={colors.textMuted}
                multiline
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
