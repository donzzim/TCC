import { Ionicons } from '@expo/vector-icons';
import { useQueryClient } from '@tanstack/react-query';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useRef, useState } from 'react';
import {
    ActivityIndicator,
    KeyboardAvoidingView,
    Platform,
    Pressable,
    ScrollView,
    StyleSheet,
    Text,
    TextInput,
    View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { ApiError } from '@/src/api/client';
import { Scanner } from '@/src/components/Scanner';
import { bemQueryOptions } from '@/src/hooks/useBens';
import { useSessao } from '@/src/session/SessionProvider';
import { useTheme } from '@/src/theme/ThemeProvider';
import { radius } from '@/src/theme/tokens';
import { registrarRecente } from '@/src/utils/recentes';

type Modo = 'consulta' | 'conferencia';

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

    /** Task 11: registrar a leitura na conferência (POST /conferencia/leituras). */
    const tratarConferencia = async (_codigo: string): Promise<void> => {
        setMensagem('A leitura para conferência ainda não está disponível.');
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
                        <Text style={{ color: colors.onPrimary, fontWeight: '700' }}>Buscar</Text>
                    </Pressable>
                </View>
            </ScrollView>
        </KeyboardAvoidingView>
    );
}

const styles = StyleSheet.create({
    flex: { flex: 1 },
    topo: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
    titulo: { fontSize: 20, fontWeight: '800' },
    mensagem: { fontSize: 14, fontWeight: '600', textAlign: 'center' },
    entrada: { flexDirection: 'row', gap: 10 },
    input: { flex: 1, borderWidth: 1, borderRadius: radius.input, paddingHorizontal: 14, paddingVertical: 12, fontSize: 16 },
    botao: { paddingHorizontal: 20, borderRadius: radius.input, alignItems: 'center', justifyContent: 'center' },
});
