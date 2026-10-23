import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { Redirect } from 'expo-router';
import { type ComponentRef, useRef, useState } from 'react';
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
import { EmptyState } from '@/src/components/EmptyState';
import { useSessao } from '@/src/session/SessionProvider';
import { useTheme } from '@/src/theme/ThemeProvider';
import { gradient, radius } from '@/src/theme/tokens';

function mensagemDeErro(erro: unknown): string {
    if (erro instanceof ApiError) {
        switch (erro.status) {
            case 0:
                return erro.message;
            case 401:
                return 'Usuário ou senha inválidos.';
            case 403:
                return 'Seu usuário não tem acesso ao aplicativo.';
            case 422:
                return 'Informe o usuário e a senha.';
            case 429:
                return 'Muitas tentativas. Aguarde um instante e tente novamente.';
            default:
                return 'Não foi possível entrar agora. Tente novamente.';
        }
    }

    return 'Não foi possível entrar agora. Tente novamente.';
}

export default function Index() {
    const { colors } = useTheme();
    const { status, sessao, entrar, recarregar } = useSessao();
    const insets = useSafeAreaInsets();
    const [veioDoLogin, setVeioDoLogin] = useState(false);
    const senhaRef = useRef<ComponentRef<typeof TextInput>>(null);

    const [login, setLogin] = useState('');
    const [senha, setSenha] = useState('');
    const [mostrarSenha, setMostrarSenha] = useState(false);
    const [enviando, setEnviando] = useState(false);
    const [erro, setErro] = useState<string | null>(null);

    if (status === 'carregando') {
        return (
            <View style={[styles.centro, { backgroundColor: colors.background }]}>
                <ActivityIndicator size="large" color={colors.primary} />
            </View>
        );
    }

    if (status === 'erro') {
        return (
            <View style={[styles.centro, { backgroundColor: colors.background }]}>
                <EmptyState
                    title="Não foi possível conectar"
                    message="Verifique sua conexão com a internet e tente novamente."
                    actionLabel="Tentar novamente"
                    onAction={() => void recarregar()}
                />
            </View>
        );
    }

    if (status === 'autenticado') {
        // Sessão salva vai direto às abas; após o login com várias lotações, escolhe o setor.
        const escolherSetor = veioDoLogin && (sessao?.lotacoes.length ?? 0) > 1;

        return <Redirect href={escolherSetor ? '/setor' : '/(tabs)'} />;
    }

    const enviar = async () => {
        if (enviando) {
            return;
        }
        if (!login.trim() || !senha) {
            setErro('Informe o usuário e a senha.');
            return;
        }

        setErro(null);
        setEnviando(true);
        setVeioDoLogin(true);
        try {
            await entrar(login.trim(), senha);
        } catch (e) {
            setVeioDoLogin(false);
            setErro(mensagemDeErro(e));
            setEnviando(false);
        }
    };

    return (
        <KeyboardAvoidingView
            style={[styles.flex, { backgroundColor: colors.background }]}
            behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        >
            <ScrollView
                contentContainerStyle={styles.scroll}
                keyboardShouldPersistTaps="handled"
                bounces={false}
            >
                <LinearGradient
                    colors={[...gradient]}
                    start={{ x: 0, y: 0 }}
                    end={{ x: 1, y: 1 }}
                    style={[styles.hero, { paddingTop: insets.top + 56 }]}
                >
                    <View style={styles.logo}>
                        <Ionicons name="cube-outline" size={34} color="#ffffff" />
                    </View>
                    <Text style={styles.heroTitulo}>E-Gap Mobile</Text>
                    <Text style={styles.heroSub}>Bens, conferência e pedidos do seu setor</Text>
                </LinearGradient>

                <View
                    style={[
                        styles.form,
                        { backgroundColor: colors.card, borderColor: colors.border },
                    ]}
                >
                    <Text style={[styles.label, { color: colors.textMuted }]}>CPF, usuário ou e-mail</Text>
                    <TextInput
                        value={login}
                        onChangeText={setLogin}
                        autoCapitalize="none"
                        autoCorrect={false}
                        autoComplete="username"
                        returnKeyType="next"
                        onSubmitEditing={() => senhaRef.current?.focus()}
                        placeholder="Digite seu acesso"
                        placeholderTextColor={colors.textMuted}
                        style={[
                            styles.input,
                            { color: colors.text, borderColor: colors.border, backgroundColor: colors.background },
                        ]}
                    />

                    <Text style={[styles.label, { color: colors.textMuted }]}>Senha</Text>
                    <View
                        style={[
                            styles.senha,
                            { borderColor: colors.border, backgroundColor: colors.background },
                        ]}
                    >
                        <TextInput
                            ref={senhaRef}
                            value={senha}
                            onChangeText={setSenha}
                            secureTextEntry={!mostrarSenha}
                            autoCapitalize="none"
                            autoCorrect={false}
                            autoComplete="password"
                            returnKeyType="go"
                            onSubmitEditing={() => void enviar()}
                            placeholder="Digite sua senha"
                            placeholderTextColor={colors.textMuted}
                            style={[styles.senhaInput, { color: colors.text }]}
                        />
                        <Pressable
                            accessibilityRole="button"
                            accessibilityLabel={mostrarSenha ? 'Ocultar senha' : 'Mostrar senha'}
                            onPress={() => setMostrarSenha((v) => !v)}
                            hitSlop={8}
                        >
                            <Ionicons
                                name={mostrarSenha ? 'eye-off-outline' : 'eye-outline'}
                                size={22}
                                color={colors.textMuted}
                            />
                        </Pressable>
                    </View>

                    {erro ? (
                        <Text accessibilityRole="alert" style={[styles.erro, { color: colors.danger }]}>
                            {erro}
                        </Text>
                    ) : null}

                    <Pressable
                        accessibilityRole="button"
                        disabled={enviando}
                        onPress={() => void enviar()}
                        style={[styles.botao, { backgroundColor: colors.primary, opacity: enviando ? 0.7 : 1 }]}
                    >
                        {enviando ? (
                            <ActivityIndicator color={colors.onPrimary} />
                        ) : (
                            <Text style={[styles.botaoTexto, { color: colors.onPrimary }]}>Entrar</Text>
                        )}
                    </Pressable>
                </View>
            </ScrollView>
        </KeyboardAvoidingView>
    );
}

const styles = StyleSheet.create({
    flex: { flex: 1 },
    centro: { flex: 1, alignItems: 'center', justifyContent: 'center' },
    scroll: { flexGrow: 1 },
    hero: {
        paddingHorizontal: 24,
        paddingBottom: 72,
        borderBottomLeftRadius: radius.sheet,
        borderBottomRightRadius: radius.sheet,
    },
    logo: {
        width: 64,
        height: 64,
        borderRadius: 20,
        backgroundColor: 'rgba(255,255,255,0.2)',
        alignItems: 'center',
        justifyContent: 'center',
        marginBottom: 16,
    },
    heroTitulo: { color: '#ffffff', fontSize: 28, fontWeight: '800' },
    heroSub: { color: 'rgba(255,255,255,0.85)', fontSize: 14, marginTop: 4 },
    form: {
        marginHorizontal: 16,
        marginTop: -44,
        padding: 18,
        borderRadius: radius.card,
        borderWidth: StyleSheet.hairlineWidth,
        gap: 6,
        shadowColor: '#1e3a8a',
        shadowOpacity: 0.12,
        shadowRadius: 20,
        shadowOffset: { width: 0, height: 8 },
        elevation: 4,
    },
    label: { fontSize: 12, fontWeight: '700', marginTop: 8 },
    input: {
        borderWidth: 1,
        borderRadius: radius.input,
        paddingHorizontal: 14,
        paddingVertical: 12,
        fontSize: 16,
    },
    senha: {
        flexDirection: 'row',
        alignItems: 'center',
        borderWidth: 1,
        borderRadius: radius.input,
        paddingHorizontal: 14,
    },
    senhaInput: { flex: 1, paddingVertical: 12, fontSize: 16 },
    erro: { fontSize: 13, marginTop: 8 },
    botao: {
        marginTop: 16,
        height: 50,
        borderRadius: radius.input,
        alignItems: 'center',
        justifyContent: 'center',
    },
    botaoTexto: { fontSize: 16, fontWeight: '800' },
});
