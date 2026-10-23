import { Ionicons } from '@expo/vector-icons';
import { useQueryClient } from '@tanstack/react-query';
import * as Haptics from 'expo-haptics';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useRef, useState } from 'react';
import {
    ActivityIndicator,
    KeyboardAvoidingView,
    Platform,
    Pressable,
    ScrollView,
    StyleSheet,
    View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Text, TextInput } from '@/src/components/Text';
import { ApiError } from '@/src/api/client';
import type { LeituraConferencia } from '@/src/api/types';
import { DivergenciaModal } from '@/src/components/DivergenciaModal';
import { Scanner } from '@/src/components/Scanner';
import { bemQueryOptions } from '@/src/hooks/useBens';
import { mensagemErro, useLerCodigo } from '@/src/hooks/useConferencia';
import { useSessao } from '@/src/session/SessionProvider';
import { useTheme } from '@/src/theme/ThemeProvider';
import { radius } from '@/src/theme/tokens';
import { registrarRecente } from '@/src/utils/recentes';

type Modo = 'consulta' | 'conferencia';

/** Mesmo código lido em sequência é ignorado por este tempo (anti-duplo). */
const JANELA_DUPLO_MS = 2000;

const TITULOS: Record<string, string> = {
    localizado: 'Bem localizado',
    ja_conferido: 'Já conferido',
    outro_setor: 'Bem de outro setor',
    situacao_nao_conferivel: 'Situação não conferível',
    em_transferencia: 'Bem em transferência',
    nao_cadastrado: 'Bem não cadastrado',
    cadastrado_manualmente: 'Cadastrado manualmente',
};

function vibrar(sucesso: boolean): void {
    void Haptics.notificationAsync(
        sucesso ? Haptics.NotificationFeedbackType.Success : Haptics.NotificationFeedbackType.Warning,
    ).catch(() => undefined);
}

export default function Leitura() {
    const { colors } = useTheme();
    const router = useRouter();
    const insets = useSafeAreaInsets();
    const queryClient = useQueryClient();
    const { sessao } = useSessao();
    const { modo: modoParam } = useLocalSearchParams<{ modo?: string }>();
    const modo: Modo = modoParam === 'conferencia' ? 'conferencia' : 'consulta';

    const [digitado, setDigitado] = useState('');
    const [consultando, setConsultando] = useState(false);
    const [mensagem, setMensagem] = useState<string | null>(null);
    const ocupado = useRef(false);
    const ultimaLeitura = useRef<{ codigo: string; em: number } | null>(null);
    const lerCodigo = useLerCodigo();
    const [resultado, setResultado] = useState<{ leitura: LeituraConferencia; codigo: string } | null>(null);
    const [divergenciaAberta, setDivergenciaAberta] = useState(false);

    /** Registra a leitura na conferência (POST /conferencia/leituras); a câmera segue aberta. */
    const tratarConferencia = async (codigo: string): Promise<void> => {
        const agora = Date.now();

        if (ultimaLeitura.current?.codigo === codigo && agora - ultimaLeitura.current.em < JANELA_DUPLO_MS) {
            return;
        }
        ultimaLeitura.current = { codigo, em: agora };

        try {
            const leitura = await lerCodigo.mutateAsync(codigo);

            setResultado({ leitura, codigo });
            vibrar(leitura.status === 'localizado');
            setDigitado('');
        } catch (e) {
            // erro de rede/servidor segue para o tratamento comum; 4xx (inclui 422) aparece inline
            if (e instanceof ApiError && (e.status === 0 || e.status >= 500)) {
                throw e;
            }
            setResultado(null);
            setMensagem(mensagemErro(e, 'Não foi possível registrar a leitura.'));
        }
    };

    const consultar = async (codigoBruto: string) => {
        const codigo = codigoBruto.trim();

        if (!codigo || ocupado.current) {
            if (!codigo) {
                setMensagem('Informe o código do patrimônio.');
            }
            return;
        }

        ocupado.current = true;
        setConsultando(true);
        setMensagem(null);
        if (modo === 'conferencia') {
            setResultado(null);
        }

        try {
            if (modo === 'conferencia') {
                await tratarConferencia(codigo);
                return;
            }

            const bem = await queryClient.fetchQuery(bemQueryOptions(sessao?.setor_atual?.id ?? null, codigo));

            await registrarRecente(String(bem.patrimonio ?? codigo), bem.descricao_resumida || bem.descricao);
            router.replace({ pathname: '/bem/[patrimonio]', params: { patrimonio: codigo } });
        } catch (e) {
            if (e instanceof ApiError && e.status === 404) {
                setMensagem('Bem não encontrado');
            } else if (e instanceof ApiError && (e.status === 0 || e.status >= 500)) {
                router.replace({ pathname: '/erro', params: { origem: 'bens' } });
            } else {
                setMensagem(e instanceof ApiError ? e.message : 'Não foi possível consultar o bem.');
            }
        } finally {
            ocupado.current = false;
            setConsultando(false);
        }
    };

    return (
        <KeyboardAvoidingView
            style={[styles.flex, { backgroundColor: colors.background }]}
            behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        >
            <ScrollView
                contentContainerStyle={{ padding: 16, paddingTop: insets.top + 12, paddingBottom: insets.bottom + 16, gap: 14 }}
                keyboardShouldPersistTaps="handled"
            >
                <View style={styles.topo}>
                    <Text style={[styles.titulo, { color: colors.text }]}>
                        {modo === 'conferencia' ? 'Ler para conferência' : 'Ler plaqueta'}
                    </Text>
                    <Pressable accessibilityRole="button" accessibilityLabel="Fechar" onPress={() => router.back()} hitSlop={10}>
                        <Ionicons name="close" size={26} color={colors.text} />
                    </Pressable>
                </View>

                <Scanner onCodigo={(c) => void consultar(c)} pausado={consultando} />

                {consultando ? <ActivityIndicator color={colors.primary} /> : null}
                {resultado ? <Feedback resultado={resultado} onDivergencia={() => setDivergenciaAberta(true)} /> : null}
                {mensagem ? (
                    <Text style={[styles.mensagem, { color: mensagem === 'Bem não encontrado' ? colors.danger : colors.warning }]}>
                        {mensagem}
                    </Text>
                ) : null}

                <Text style={{ color: colors.textMuted, fontSize: 13 }}>Ou digite o código do patrimônio</Text>
                <View style={styles.entrada}>
                    <TextInput
                        value={digitado}
                        onChangeText={setDigitado}
                        placeholder="Ex.: 104552"
                        placeholderTextColor={colors.textMuted}
                        autoCapitalize="characters"
                        autoCorrect={false}
                        returnKeyType="search"
                        onSubmitEditing={() => void consultar(digitado)}
                        style={[styles.input, { color: colors.text, backgroundColor: colors.card, borderColor: colors.border }]}
                    />
                    <Pressable
                        accessibilityRole="button"
                        onPress={() => void consultar(digitado)}
                        disabled={consultando}
                        style={[styles.botao, { backgroundColor: colors.primary, opacity: consultando ? 0.6 : 1 }]}
                    >
                        <Text style={{ color: colors.onPrimary, fontWeight: '700' }}>{modo === 'conferencia' ? 'Registrar' : 'Buscar'}</Text>
                    </Pressable>
                </View>
            </ScrollView>
            {modo === 'conferencia' ? (
                <DivergenciaModal
                    visible={divergenciaAberta}
                    onClose={() => setDivergenciaAberta(false)}
                    codigoInicial={resultado?.codigo ?? ''}
                    onRegistrado={(texto) => {
                        setResultado(null);
                        setMensagem(texto);
                    }}
                />
            ) : null}
        </KeyboardAvoidingView>
    );
}

function Feedback({
    resultado,
    onDivergencia,
}: {
    resultado: { leitura: LeituraConferencia; codigo: string };
    onDivergencia: () => void;
}) {
    const { colors } = useTheme();
    const { leitura } = resultado;
    const cor =
        leitura.status === 'localizado'
            ? colors.success
            : leitura.status === 'ja_conferido'
              ? colors.primary
              : leitura.status === 'nao_cadastrado'
                ? colors.danger
                : colors.warning;
    const bem = leitura.bem;

    return (
        <View accessibilityRole="alert" style={[styles.feedback, { backgroundColor: `${cor}1f`, borderColor: cor }]}>
            <Text style={{ color: cor, fontSize: 16, fontWeight: '800' }}>{TITULOS[leitura.status] ?? leitura.status}</Text>
            <Text style={{ color: colors.text }}>{leitura.message}</Text>
            {bem ? (
                <Text style={{ color: colors.textMuted, fontSize: 13 }}>
                    {bem.descricao_resumida || bem.descricao || 'Sem descrição'} · Nº {bem.patrimonio ?? '-'}
                </Text>
            ) : null}
            {bem?.situacao_bem_id === 8 ? (
                <Text style={{ color: colors.warning, fontWeight: '700' }}>Verifique o termo</Text>
            ) : null}
            {leitura.status === 'nao_cadastrado' ? (
                <Pressable
                    accessibilityRole="button"
                    onPress={onDivergencia}
                    style={[styles.atalho, { backgroundColor: colors.danger }]}
                >
                    <Text style={{ color: '#ffffff', fontWeight: '700' }}>Registrar divergência</Text>
                </Pressable>
            ) : null}
        </View>
    );
}

const styles = StyleSheet.create({
    flex: { flex: 1 },
    topo: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
    titulo: { fontSize: 20, fontWeight: '800' },
    mensagem: { fontSize: 14, fontWeight: '600', textAlign: 'center' },
    entrada: { flexDirection: 'row', gap: 10 },
    input: { flex: 1, borderWidth: 1, borderRadius: radius.input, paddingHorizontal: 14, paddingVertical: 12, fontSize: 16 },
    feedback: { borderWidth: 1.5, borderRadius: radius.card, padding: 14, gap: 4 },
    atalho: { alignSelf: 'flex-start', marginTop: 6, paddingHorizontal: 16, paddingVertical: 9, borderRadius: radius.pill },
    botao: { paddingHorizontal: 20, borderRadius: radius.input, alignItems: 'center', justifyContent: 'center' },
});
