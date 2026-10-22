import MaterialIcons from '@expo/vector-icons/MaterialIcons';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { AppMenuButton } from '@/components/app-menu-button';
import { useThemeStyles } from '@/src/theme/useThemeStyles';

type TutorialStep = {
  icon: keyof typeof MaterialIcons.glyphMap;
  title: string;
  description: string;
};

type TutorialSection = {
  title: string;
  subtitle: string;
  icon: keyof typeof MaterialIcons.glyphMap;
  steps: TutorialStep[];
};

const TUTORIAL_SECTIONS: TutorialSection[] = [
  {
    title: 'Acesso e navegação',
    subtitle: 'Como entrar no sistema e circular pelos módulos.',
    icon: 'login',
    steps: [
      {
        icon: 'person-outline',
        title: 'Entre com suas credenciais',
        description:
          'Informe usuário e senha. Ao autenticar, o app guarda a sessão e abre o painel de Patrimônio.',
      },
      {
        icon: 'menu',
        title: 'Use o menu lateral',
        description:
          'Toque no botão de menu ou arraste a borda esquerda para acessar Patrimônio, Pedidos, Tutorial e Sair.',
      },
      {
        icon: 'dashboard',
        title: 'Use a barra inferior',
        description:
          'No módulo Patrimônio, navegue rapidamente entre Dashboard, Bens e Conferência pela barra inferior.',
      },
    ],
  },
  {
    title: 'Dashboard patrimonial',
    subtitle: 'Resumo do setor, consulta de bens e leituras rápidas.',
    icon: 'dashboard',
    steps: [
      {
        icon: 'analytics',
        title: 'Confira os indicadores',
        description:
          'A tela mostra totais de bens, valores, situação patrimonial e andamento da conferência atual.',
      },
      {
        icon: 'search',
        title: 'Consulte um patrimônio',
        description:
          'Digite o código patrimonial, tombo, tombo SMARAPD ou patrimônio anterior para abrir os detalhes do bem.',
      },
      {
        icon: 'qr-code-scanner',
        title: 'Leia pela câmera',
        description:
          'Use a câmera para escanear o código do bem. As últimas consultas ficam salvas no histórico local.',
      },
    ],
  },
  {
    title: 'Bens do setor',
    subtitle: 'Listagem da carga patrimonial vinculada ao usuário.',
    icon: 'inventory-2',
    steps: [
      {
        icon: 'apartment',
        title: 'Valide o contexto',
        description:
          'O app resolve sua unidade e setor pelo token, não permitindo escolher outro escopo manualmente.',
      },
      {
        icon: 'manage-search',
        title: 'Busque na lista',
        description:
          'Filtre por patrimônio, descrição, marca, modelo ou série para encontrar itens do setor com mais rapidez.',
      },
      {
        icon: 'refresh',
        title: 'Atualize e carregue mais',
        description:
          'Puxe para atualizar os dados ou role até o fim para buscar a próxima página de bens.',
      },
    ],
  },
  {
    title: 'Conferência de inventário',
    subtitle: 'Registro do que foi encontrado, divergente ou não localizado.',
    icon: 'fact-check',
    steps: [
      {
        icon: 'filter-list',
        title: 'Acompanhe os status',
        description:
          'Use os filtros para ver todos, pendentes, localizados, não localizados, divergentes e itens em transferência.',
      },
      {
        icon: 'pin',
        title: 'Valide uma leitura',
        description:
          'Digite ou escaneie um código. o EGAP informa se o bem pode ser localizado, já foi conferido ou pertence a outro contexto.',
      },
      {
        icon: 'task-alt',
        title: 'Registre a situação',
        description:
          'Confirme Localizado, informe justificativa para Não localizado ou descreva campos divergentes antes de finalizar.',
      },
    ],
  },
  {
    title: 'Pedidos',
    subtitle: 'Solicitações de consumo e bens permanentes pelo carrinho.',
    icon: 'shopping-cart',
    steps: [
      {
        icon: 'category',
        title: 'Escolha o tipo de pedido',
        description:
          'Consumo encaminha materiais ao Almoxarifado. Permanentes seguem para Patrimônio e aceitam adição ou substituição.',
      },
      {
        icon: 'add-shopping-cart',
        title: 'Monte o carrinho',
        description:
          'Pesquise materiais, ajuste quantidades e selecione o complemento do setor que receberá o pedido.',
      },
      {
        icon: 'assignment-turned-in',
        title: 'Preencha as justificativas',
        description:
          'Pedidos de consumo exigem justificativa geral. Permanentes exigem justificativa por item e patrimônio substituído quando aplicável.',
      },
    ],
  },
  {
    title: 'Falhas e cuidados',
    subtitle: 'Como interpretar mensagens e evitar retrabalho.',
    icon: 'verified-user',
    steps: [
      {
        icon: 'wifi-off',
        title: 'Verifique a conexão',
        description:
          'Falhas de rede ou servidor abrem uma tela de erro com detalhes. Confira internet e URL antes de repetir.',
      },
      {
        icon: 'lock',
        title: 'Respeite bloqueios da API',
        description:
          'A atividade finalizada ou sem permissão de edição bloqueia novas ações de conferência para preservar a auditoria.',
      },
      {
        icon: 'logout',
        title: 'Finalize sua sessão',
        description:
          'Use Sair no menu lateral para revogar o token atual e voltar à tela de login.',
      },
    ],
  },
];

export default function TutorialScreen() {
  const themed = useThemeStyles();

  return (
    <SafeAreaView style={[styles.safeArea, themed.screen]}>
      <View style={[styles.header, { borderBottomColor: themed.colors.border }]}>
        <AppMenuButton />
        <View style={styles.headerText}>
          <Text style={[styles.headerLabel, themed.mutedText]}>Configurações</Text>
          <Text style={[styles.headerTitle, themed.text]}>Tutorial</Text>
        </View>
      </View>

      <ScrollView
        style={styles.scroll}
        contentContainerStyle={styles.content}
        showsVerticalScrollIndicator={false}>
        <View style={[styles.introPanel, themed.surface]}>
          <View style={[styles.introIcon, themed.primarySurface]}>
            <MaterialIcons name="school" size={28} color={themed.colors.primary} />
          </View>
          <View style={styles.introText}>
            <Text style={[styles.introTitle, themed.text]}>Passo a passo do aplicativo</Text>
            <Text style={[styles.introDescription, themed.mutedText]}>
              Consulte este guia para entender o fluxo do E-Gap Mobile: acesso, patrimônio,
              conferência e pedidos.
            </Text>
          </View>
        </View>

        {TUTORIAL_SECTIONS.map((section, sectionIndex) => (
          <View key={section.title} style={[styles.sectionCard, themed.surface]}>
            <View style={styles.sectionHeader}>
              <View style={[styles.sectionIcon, themed.primarySurface]}>
                <MaterialIcons name={section.icon} size={23} color={themed.colors.primary} />
              </View>
              <View style={styles.sectionTitleGroup}>
                <Text style={[styles.sectionTitle, themed.text]}>{section.title}</Text>
                <Text style={[styles.sectionSubtitle, themed.mutedText]}>{section.subtitle}</Text>
              </View>
            </View>

            <View style={styles.steps}>
              {section.steps.map((step, stepIndex) => (
                <View key={step.title} style={styles.stepRow}>
                  <View style={styles.stepMarkerGroup}>
                    <View style={[styles.stepMarker, { backgroundColor: themed.colors.primary }]}>
                      <Text style={[styles.stepNumber, themed.onPrimaryText]}>
                        {sectionIndex + 1}.{stepIndex + 1}
                      </Text>
                    </View>
                    {stepIndex < section.steps.length - 1 ? (
                      <View style={[styles.stepLine, { backgroundColor: themed.colors.border }]} />
                    ) : null}
                  </View>
                  <View style={[styles.stepContent, themed.mutedSurface]}>
                    <View style={[styles.stepIcon, themed.primarySurface]}>
                      <MaterialIcons name={step.icon} size={20} color={themed.colors.primary} />
                    </View>
                    <View style={styles.stepText}>
                      <Text style={[styles.stepTitle, themed.text]}>{step.title}</Text>
                      <Text style={[styles.stepDescription, themed.mutedText]}>
                        {step.description}
                      </Text>
                    </View>
                  </View>
                </View>
              ))}
            </View>
          </View>
        ))}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
  },
  header: {
    minHeight: 76,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    borderBottomWidth: 1,
    paddingHorizontal: 16,
    paddingVertical: 12,
  },
  headerText: {
    flex: 1,
    gap: 2,
  },
  headerLabel: {
    fontSize: 12,
    fontWeight: '800',
    textTransform: 'uppercase',
  },
  headerTitle: {
    fontSize: 22,
    fontWeight: '800',
  },
  scroll: {
    flex: 1,
  },
  content: {
    gap: 14,
    padding: 16,
    paddingBottom: 28,
  },
  introPanel: {
    minHeight: 108,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    borderWidth: 1,
    borderRadius: 8,
    padding: 14,
  },
  introIcon: {
    width: 50,
    height: 50,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
  },
  introText: {
    flex: 1,
    gap: 5,
  },
  introTitle: {
    fontSize: 18,
    fontWeight: '800',
  },
  introDescription: {
    fontSize: 13,
    lineHeight: 19,
    fontWeight: '700',
  },
  sectionCard: {
    gap: 14,
    borderWidth: 1,
    borderRadius: 8,
    padding: 14,
  },
  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 11,
  },
  sectionIcon: {
    width: 44,
    height: 44,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
  },
  sectionTitleGroup: {
    flex: 1,
    gap: 3,
  },
  sectionTitle: {
    fontSize: 17,
    fontWeight: '800',
  },
  sectionSubtitle: {
    fontSize: 12,
    lineHeight: 17,
    fontWeight: '700',
  },
  steps: {
    gap: 0,
  },
  stepRow: {
    flexDirection: 'row',
    gap: 10,
  },
  stepMarkerGroup: {
    alignItems: 'center',
  },
  stepMarker: {
    minWidth: 34,
    height: 28,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
    paddingHorizontal: 7,
  },
  stepNumber: {
    fontSize: 11,
    fontWeight: '900',
  },
  stepLine: {
    width: 2,
    flex: 1,
    minHeight: 12,
  },
  stepContent: {
    flex: 1,
    minHeight: 82,
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 10,
    borderWidth: 1,
    borderRadius: 8,
    marginBottom: 10,
    padding: 11,
  },
  stepIcon: {
    width: 36,
    height: 36,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
  },
  stepText: {
    flex: 1,
    gap: 4,
  },
  stepTitle: {
    fontSize: 14,
    fontWeight: '800',
  },
  stepDescription: {
    fontSize: 12,
    lineHeight: 17,
    fontWeight: '700',
  },
});
