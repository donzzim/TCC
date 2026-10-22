import MaterialIcons from '@expo/vector-icons/MaterialIcons';
import { router } from 'expo-router';
import { useEffect, useState } from 'react';
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
import { SafeAreaView } from 'react-native-safe-area-context';
import { ApiError, NetworkError } from '@/src/api/errors';
import { authApi } from '@/src/api/auth';
import { useThemeStyles } from '@/src/theme/useThemeStyles';

export default function LoginScreen() {
  const themed = useThemeStyles();
  const [login, setLogin] = useState('');
  const [password, setPassword] = useState('');
  const [isPasswordVisible, setIsPasswordVisible] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isCheckingSession, setIsCheckingSession] = useState(true);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  useEffect(() => {
    let isMounted = true;

    async function restoreSession() {
      const session = await authApi.getStoredSession();

      if (!isMounted) {
        return;
      }

      if (session) {
        router.replace('/patrimonio/principal');
        return;
      }

      setIsCheckingSession(false);
    }

    restoreSession();

    return () => {
      isMounted = false;
    };
  }, []);

  async function handleLogin() {
    const trimmedLogin = login.trim();

    if (!trimmedLogin || !password) {
      setErrorMessage('Informe usuário e senha para continuar.');
      return;
    }

    setIsSubmitting(true);
    setErrorMessage(null);

    try {
      await authApi.login(trimmedLogin, password);
      router.replace('/patrimonio/principal');
    } catch (error) {
      if (error instanceof ApiError || error instanceof NetworkError) {
        setErrorMessage(error.message);
      } else {
        setErrorMessage('Não foi possível realizar o login.');
      }
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <SafeAreaView style={[styles.safeArea, themed.screen]}>
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        style={styles.keyboardContainer}>
        <ScrollView
          contentContainerStyle={styles.scrollContent}
          keyboardShouldPersistTaps="handled"
          showsVerticalScrollIndicator={false}>
          <View style={styles.brandHeader}>
            <View style={[styles.logoMark, { backgroundColor: themed.colors.primary }]}>
              <MaterialIcons name="inventory-2" size={30} color={themed.colors.primaryText} />
            </View>
            <View style={styles.brandTextGroup}>
              <Text style={[styles.eyebrow, themed.mutedText]}>Conferência patrimonial</Text>
              <Text style={[styles.title, themed.text]}>E-Gap Mobile</Text>
            </View>
          </View>

          <View style={[styles.formCard, themed.surface]}>
            <View style={styles.formHeader}>
              <Text style={[styles.formTitle, themed.text]}>Acesso ao sistema</Text>
              <Text style={[styles.formDescription, themed.mutedText]}>
                Informe suas credenciais para acessar a rotina mobile de inventário.
              </Text>
            </View>

            <View style={styles.field}>
              <Text style={[styles.label, themed.mutedText]}>Usuário</Text>
              <View style={[styles.inputWrapper, themed.input]}>
                <MaterialIcons name="person-outline" size={22} color={themed.colors.textMuted} />
                <TextInput
                  placeholder="Digite seu usuário"
                  placeholderTextColor={themed.colors.textSubtle}
                  autoCapitalize="none"
                  autoCorrect={false}
                  editable={!isSubmitting && !isCheckingSession}
                  returnKeyType="next"
                  value={login}
                  onChangeText={setLogin}
                  style={[styles.input, themed.text]}
                />
              </View>
            </View>

            <View style={styles.field}>
              <Text style={[styles.label, themed.mutedText]}>Senha</Text>
              <View style={[styles.inputWrapper, themed.input]}>
                <MaterialIcons name="lock-outline" size={22} color={themed.colors.textMuted} />
                <TextInput
                  placeholder="Digite sua senha"
                  placeholderTextColor={themed.colors.textSubtle}
                  secureTextEntry={!isPasswordVisible}
                  editable={!isSubmitting && !isCheckingSession}
                  returnKeyType="done"
                  value={password}
                  onChangeText={setPassword}
                  onSubmitEditing={handleLogin}
                  style={[styles.input, themed.text]}
                />
                <Pressable
                  accessibilityLabel={isPasswordVisible ? 'Ocultar senha' : 'Visualizar senha'}
                  accessibilityRole="button"
                  disabled={isSubmitting || isCheckingSession}
                  hitSlop={10}
                  onPress={() => setIsPasswordVisible((currentValue) => !currentValue)}
                  style={({ pressed }) => [
                    styles.passwordVisibilityButton,
                    pressed && { backgroundColor: themed.colors.primarySoft },
                  ]}>
                  <MaterialIcons
                    name={isPasswordVisible ? 'visibility-off' : 'visibility'}
                    size={22}
                    color={themed.colors.textMuted}
                  />
                </Pressable>
              </View>
            </View>

            {errorMessage ? (
              <View style={[styles.errorPanel, themed.dangerSurface]}>
                <MaterialIcons name="error-outline" size={20} color={themed.colors.danger} />
                <Text style={[styles.errorText, { color: themed.colors.danger }]}>{errorMessage}</Text>
              </View>
            ) : null}

            <Pressable
              disabled={isSubmitting || isCheckingSession}
              onPress={handleLogin}
              style={({ pressed }) => [
                styles.primaryButton,
                (pressed || isSubmitting || isCheckingSession) && styles.primaryButtonPressed,
                { backgroundColor: themed.colors.primary },
              ]}>
              {isSubmitting || isCheckingSession ? (
                <ActivityIndicator color={themed.colors.primaryText} />
              ) : (
                <MaterialIcons name="login" size={21} color={themed.colors.primaryText} />
              )}
              <Text style={[styles.primaryButtonText, themed.onPrimaryText]}>
                {isCheckingSession ? 'Verificando acesso' : isSubmitting ? 'Entrando' : 'Entrar'}
              </Text>
            </Pressable>
          </View>

          <View style={[styles.footerPanel, themed.primarySurface]}>
            <MaterialIcons name="verified-user" size={20} color={themed.colors.primary} />
            <Text style={[styles.footerText, themed.mutedText]}>
              Ambiente preparado para acesso institucional ao controle de bens do setor.
            </Text>
          </View>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#F4F7FA',
  },
  keyboardContainer: {
    flex: 1,
  },
  scrollContent: {
    flexGrow: 1,
    justifyContent: 'center',
    gap: 24,
    padding: 24,
  },
  brandHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 14,
  },
  logoMark: {
    width: 58,
    height: 58,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
    backgroundColor: '#1E4E79',
  },
  brandTextGroup: {
    flex: 1,
    gap: 4,
  },
  eyebrow: {
    color: '#627D98',
    fontSize: 13,
    fontWeight: '800',
    textTransform: 'uppercase',
  },
  title: {
    color: '#102A43',
    fontSize: 33,
    fontWeight: '800',
  },
  formCard: {
    gap: 18,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#D9E2EC',
    backgroundColor: '#FFFFFF',
    padding: 18,
  },
  formHeader: {
    gap: 6,
  },
  formTitle: {
    color: '#102A43',
    fontSize: 21,
    fontWeight: '800',
  },
  formDescription: {
    color: '#52616B',
    fontSize: 14,
    lineHeight: 20,
    fontWeight: '600',
  },
  field: {
    gap: 8,
  },
  label: {
    color: '#334E68',
    fontSize: 14,
    fontWeight: '800',
  },
  inputWrapper: {
    minHeight: 52,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#BCCCDC',
    backgroundColor: '#FFFFFF',
    paddingHorizontal: 12,
  },
  input: {
    flex: 1,
    color: '#102A43',
    fontSize: 16,
    fontWeight: '700',
  },
  passwordVisibilityButton: {
    width: 36,
    height: 36,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
  },
  passwordVisibilityButtonPressed: {
    backgroundColor: '#EAF4FB',
  },
  primaryButton: {
    minHeight: 52,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    borderRadius: 8,
    backgroundColor: '#1E4E79',
  },
  primaryButtonPressed: {
    opacity: 0.78,
  },
  primaryButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '800',
  },
  errorPanel: {
    minHeight: 46,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 9,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#F5C2C7',
    backgroundColor: '#FFF5F5',
    paddingHorizontal: 12,
    paddingVertical: 10,
  },
  errorText: {
    flex: 1,
    color: '#9B2C2C',
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '800',
  },
  footerPanel: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#D9E8F5',
    backgroundColor: '#EAF4FB',
    padding: 14,
  },
  footerText: {
    flex: 1,
    color: '#334E68',
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '700',
  },
});
