import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { Text } from '@/src/components/Text';
import { Card } from '@/src/components/Card';
import { GradientHeader } from '@/src/components/GradientHeader';
import { Screen } from '@/src/components/Screen';
import { SetorSheet } from '@/src/components/SetorSheet';
import { useSessao } from '@/src/session/SessionProvider';
import { useTheme, type TemaPreferencia } from '@/src/theme/ThemeProvider';
import { radius } from '@/src/theme/tokens';

const TEMAS: { valor: TemaPreferencia; rotulo: string }[] = [
    { valor: 'sistema', rotulo: 'Sistema' },
    { valor: 'claro', rotulo: 'Claro' },
    { valor: 'escuro', rotulo: 'Escuro' },
];

function Linha({ rotulo, valor }: { rotulo: string; valor: string | null | undefined }) {
    const { colors } = useTheme();

    if (!valor) {
        return null;
    }

    return (
        <View style={styles.linha}>
            <Text style={[styles.rotulo, { color: colors.textMuted }]}>{rotulo}</Text>
            <Text style={[styles.valor, { color: colors.text }]}>{valor}</Text>
        </View>
    );
}

export default function Perfil() {
    const { colors, preferencia, setPreferencia } = useTheme();
    const { sessao, sair } = useSessao();
    const router = useRouter();
    const [setorAberto, setSetorAberto] = useState(false);
    const [saindo, setSaindo] = useState(false);

    const usuario = sessao?.usuario;
    const podeTrocarSetor = !sessao?.lotacao_legada && (sessao?.lotacoes.length ?? 0) > 1;
    const setor = sessao?.setor_atual;

    const aoSair = async () => {
        setSaindo(true);
        await sair();
    };

    return (
        <Screen safeTop={false}>
            <GradientHeader
                title={usuario?.nome ?? 'Perfil'}
                subtitle={usuario?.cargo ?? 'Perfil'}
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

            <View style={styles.corpo}>
                <Card>
                    <Linha rotulo="Usuário" valor={usuario?.login} />
                    <Linha rotulo="E-mail" valor={usuario?.email} />
                    <Linha rotulo="CPF" valor={usuario?.cpf} />
                    <Linha rotulo="Setor" valor={setor?.setor} />
                    <Linha rotulo="Unidade" valor={setor?.unidade} />
                    {podeTrocarSetor ? (
                        <Pressable
                            accessibilityRole="button"
                            onPress={() => setSetorAberto(true)}
                            style={[styles.botaoSec, { borderColor: colors.primary }]}
                        >
                            <Text style={{ color: colors.primary, fontWeight: '700' }}>Trocar setor</Text>
                        </Pressable>
                    ) : null}
                </Card>

                <Card>
                    <Text style={[styles.secao, { color: colors.text }]}>Tema</Text>
                    <View style={styles.temas}>
                        {TEMAS.map((tema) => {
                            const ativo = preferencia === tema.valor;

                            return (
                                <Pressable
                                    key={tema.valor}
                                    accessibilityRole="button"
                                    accessibilityState={{ selected: ativo }}
                                    onPress={() => setPreferencia(tema.valor)}
                                    style={[
                                        styles.tema,
                                        {
                                            backgroundColor: ativo ? colors.primary : colors.background,
                                            borderColor: ativo ? colors.primary : colors.border,
                                        },
                                    ]}
                                >
                                    <Text
                                        style={{
                                            color: ativo ? colors.onPrimary : colors.text,
                                            fontWeight: '700',
                                        }}
                                    >
                                        {tema.rotulo}
                                    </Text>
                                </Pressable>
                            );
                        })}
                    </View>
                </Card>

                <Pressable accessibilityRole="button" onPress={() => router.push('/tutorial')}>
                    <Card style={styles.itemLink}>
                        <Ionicons name="help-circle-outline" size={22} color={colors.primary} />
                        <Text style={[styles.itemLinkTexto, { color: colors.text }]}>Como usar o app</Text>
                        <Ionicons name="chevron-forward" size={20} color={colors.textMuted} />
                    </Card>
                </Pressable>

                <Pressable
                    accessibilityRole="button"
                    disabled={saindo}
                    onPress={() => void aoSair()}
                    style={[styles.sair, { borderColor: colors.danger, opacity: saindo ? 0.6 : 1 }]}
                >
                    <Text style={{ color: colors.danger, fontWeight: '800' }}>Sair</Text>
                </Pressable>
            </View>

            <SetorSheet visible={setorAberto} onClose={() => setSetorAberto(false)} />
        </Screen>
    );
}

const styles = StyleSheet.create({
    corpo: { padding: 16, gap: 14 },
    linha: { paddingVertical: 6 },
    rotulo: { fontSize: 11, fontWeight: '700' },
    valor: { fontSize: 15, marginTop: 1 },
    botaoSec: {
        marginTop: 10,
        borderWidth: 1.5,
        borderRadius: radius.input,
        paddingVertical: 11,
        alignItems: 'center',
    },
    secao: { fontSize: 14, fontWeight: '800', marginBottom: 10 },
    temas: { flexDirection: 'row', gap: 8 },
    tema: { flex: 1, borderWidth: 1, borderRadius: radius.input, paddingVertical: 10, alignItems: 'center' },
    itemLink: { flexDirection: 'row', alignItems: 'center', gap: 12 },
    itemLinkTexto: { flex: 1, fontSize: 15, fontWeight: '600' },
    sair: { borderWidth: 1.5, borderRadius: radius.input, paddingVertical: 14, alignItems: 'center' },
});
