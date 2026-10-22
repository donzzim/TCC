import MaterialIcons from '@expo/vector-icons/MaterialIcons';
import { router, usePathname, type Href } from 'expo-router';
import { useState } from 'react';
import {
  ActivityIndicator,
  Modal,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  useWindowDimensions,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { authApi } from '@/src/api/auth';
import {
  setPatrimonioNavigationDirectionFromRoutes,
  type PatrimonioRoute,
} from '@/src/navigation/patrimonioNavigation';
import { useAppTheme } from '@/src/theme/appTheme';

const SIDEBAR_MAX_WIDTH = 360;
const SIDEBAR_MIN_WIDTH = 280;
const SIDEBAR_SCREEN_RATIO = 0.84;
const SIDEBAR_HORIZONTAL_MARGIN = 18;

type AppRoute =
  | '/patrimonio/principal'
  | '/patrimonio/bens'
  | '/patrimonio/conferencia'
  | '/pedidos/consumo'
  | '/pedidos/permanentes'
  | '/configuracoes/tutorial';

interface SidebarItem {
  href: AppRoute;
  label: string;
  description: string;
  icon: keyof typeof MaterialIcons.glyphMap;
}

interface AppSidebarProps {
  visible: boolean;
  onClose: () => void;
}

const PATRIMONIO_ITEMS: SidebarItem[] = [
  {
    href: '/patrimonio/principal',
    label: 'Dashboard',
    description: 'Resumo, estatísticas e leitura patrimonial',
    icon: 'dashboard',
  },
  {
    href: '/patrimonio/bens',
    label: 'Bens do setor',
    description: 'Consulta da carga patrimonial vinculada',
    icon: 'inventory-2',
  },
  {
    href: '/patrimonio/conferencia',
    label: 'Conferência',
    description: 'Inventario, localização e divergências',
    icon: 'fact-check',
  },
];

const PEDIDOS_ITEMS: SidebarItem[] = [
  {
    href: '/pedidos/consumo',
    label: 'Bens de Consumo',
    description: 'Carrinho de materiais de almoxarifado',
    icon: 'shopping-basket',
  },
  {
    href: '/pedidos/permanentes',
    label: 'Bens Permanentes',
    description: 'Carrinho de materiais patrimoniais',
    icon: 'inventory-2',
  },
];

const CONFIGURACOES_ITEMS: SidebarItem[] = [
  {
    href: '/configuracoes/tutorial',
    label: 'Tutorial',
    description: 'Passo a passo de uso do aplicativo',
    icon: 'school',
  },
];

export function AppSidebar({ visible, onClose }: AppSidebarProps) {
  const { colors } = useAppTheme();
  const pathname = usePathname();
  const insets = useSafeAreaInsets();
  const { width: windowWidth } = useWindowDimensions();
  const [isPatrimonioOpen, setIsPatrimonioOpen] = useState(true);
  const [isPedidosOpen, setIsPedidosOpen] = useState(true);
  const [isConfiguracoesOpen, setIsConfiguracoesOpen] = useState(true);
  const [isLoggingOut, setIsLoggingOut] = useState(false);
  const sidebarWidth = Math.min(
    SIDEBAR_MAX_WIDTH,
    Math.max(
      Math.min(SIDEBAR_MIN_WIDTH, windowWidth - SIDEBAR_HORIZONTAL_MARGIN),
      windowWidth * SIDEBAR_SCREEN_RATIO,
    ),
  );

  function handleNavigate(href: SidebarItem['href']) {
    onClose();

    if (pathname === href) {
      return;
    }

    if (href.startsWith('/patrimonio')) {
      setPatrimonioNavigationDirectionFromRoutes(pathname, href as PatrimonioRoute);
    }

    router.replace(href as Href);
  }

  function handleOpenPatrimonio() {
    onClose();

    if (pathname.startsWith('/patrimonio')) {
      return;
    }

    router.replace('/patrimonio' as Href);
  }

  function handleOpenPedidos() {
    onClose();

    if (pathname.startsWith('/pedidos')) {
      return;
    }

    router.replace('/pedidos' as Href);
  }

  async function handleLogout() {
    setIsLoggingOut(true);
    onClose();

    try {
      await authApi.logout();
    } finally {
      router.replace('/');
    }
  }

  function renderItems(items: SidebarItem[]) {
    return (
      <View style={styles.items}>
        {items.map((item) => {
          const isActive = pathname === item.href;

          return (
            <Pressable
              key={item.href}
              onPress={() => handleNavigate(item.href)}
              style={({ pressed }) => [
                styles.item,
                { backgroundColor: colors.surfaceMuted },
                isActive && { backgroundColor: colors.primary },
                pressed && !isActive && { backgroundColor: colors.primarySoft },
              ]}>
              <View
                style={[
                  styles.itemIcon,
                  { backgroundColor: colors.primarySoft },
                  isActive && styles.itemIconActive,
                ]}>
                <MaterialIcons
                  name={item.icon}
                  size={19}
                  color={isActive ? colors.primaryText : colors.primary}
                />
              </View>
              <View style={styles.itemText}>
                <Text
                  style={[
                    styles.itemTitle,
                    { color: colors.text },
                    isActive && { color: colors.primaryText },
                  ]}>
                  {item.label}
                </Text>
                <Text
                  style={[
                    styles.itemDescription,
                    { color: colors.textMuted },
                    isActive && { color: colors.primaryText },
                  ]}
                  numberOfLines={2}>
                  {item.description}
                </Text>
              </View>
            </Pressable>
          );
        })}
      </View>
    );
  }

  return (
    <Modal
      animationType="fade"
      transparent
      visible={visible}
      statusBarTranslucent
      onRequestClose={onClose}>
      <View style={[styles.overlay, { backgroundColor: colors.overlay }]}>
        <Pressable style={styles.backdrop} onPress={onClose} />
        <View
          style={[
            styles.sidebar,
            {
              backgroundColor: colors.surface,
              width: sidebarWidth,
              paddingTop: insets.top,
              paddingBottom: insets.bottom,
            },
          ]}>
          <View style={[styles.header, { borderBottomColor: colors.border }]}>
            <View style={[styles.brandIcon, { backgroundColor: colors.primarySoft }]}>
              <MaterialIcons name="apps" size={22} color={colors.primary} />
            </View>
            <View style={styles.headerText}>
              <Text style={[styles.headerLabel, { color: colors.textMuted }]}>EGap Mobile</Text>
              <Text style={[styles.headerTitle, { color: colors.text }]}>Menu</Text>
            </View>
            <Pressable
              onPress={onClose}
              style={[
                styles.iconButton,
                {
                  backgroundColor: colors.primarySoft,
                  borderColor: colors.borderAccent,
                },
              ]}>
              <MaterialIcons name="close" size={21} color={colors.primary} />
            </Pressable>
          </View>

          <ScrollView
            style={styles.scroll}
            contentContainerStyle={styles.content}
            showsVerticalScrollIndicator={false}>
            <View style={styles.group}>
              <View
                style={[
                  styles.groupHeader,
                  {
                    backgroundColor: colors.surfaceMuted,
                    borderColor: colors.border,
                  },
                ]}>
                <Pressable
                  onPress={handleOpenPatrimonio}
                  style={({ pressed }) => [
                    styles.groupMain,
                    pressed && styles.pressed,
                  ]}>
                  <View style={[styles.groupIcon, { backgroundColor: colors.primarySoft }]}>
                    <MaterialIcons name="account-balance" size={21} color={colors.primary} />
                  </View>
                  <View style={styles.groupText}>
                    <Text style={[styles.groupTitle, { color: colors.text }]}>Patrimônio</Text>
                    <Text style={[styles.groupMeta, { color: colors.textMuted }]}>
                      {PATRIMONIO_ITEMS.length} funcionalidades
                    </Text>
                  </View>
                </Pressable>
                <Pressable
                  onPress={() => setIsPatrimonioOpen((currentValue) => !currentValue)}
                  style={({ pressed }) => [
                    styles.collapseButton,
                    pressed && styles.pressed,
                  ]}>
                  <MaterialIcons
                    name={isPatrimonioOpen ? 'keyboard-arrow-up' : 'keyboard-arrow-down'}
                    size={23}
                    color={colors.textMuted}
                  />
                </Pressable>
              </View>

              {isPatrimonioOpen ? (
                renderItems(PATRIMONIO_ITEMS)
              ) : null}
            </View>

            <View style={styles.group}>
              <View
                style={[
                  styles.groupHeader,
                  {
                    backgroundColor: colors.surfaceMuted,
                    borderColor: colors.border,
                  },
                ]}>
                <Pressable
                  onPress={handleOpenPedidos}
                  style={({ pressed }) => [
                    styles.groupMain,
                    pressed && styles.pressed,
                  ]}>
                  <View style={[styles.groupIcon, { backgroundColor: colors.primarySoft }]}>
                    <MaterialIcons name="shopping-cart" size={21} color={colors.primary} />
                  </View>
                  <View style={styles.groupText}>
                    <Text style={[styles.groupTitle, { color: colors.text }]}>Pedidos</Text>
                    <Text style={[styles.groupMeta, { color: colors.textMuted }]}>
                      {PEDIDOS_ITEMS.length} funcionalidades
                    </Text>
                  </View>
                </Pressable>
                <Pressable
                  onPress={() => setIsPedidosOpen((currentValue) => !currentValue)}
                  style={({ pressed }) => [
                    styles.collapseButton,
                    pressed && styles.pressed,
                  ]}>
                  <MaterialIcons
                    name={isPedidosOpen ? 'keyboard-arrow-up' : 'keyboard-arrow-down'}
                    size={23}
                    color={colors.textMuted}
                  />
                </Pressable>
              </View>

              {isPedidosOpen ? renderItems(PEDIDOS_ITEMS) : null}
            </View>

            <View style={styles.group}>
              <View
                style={[
                  styles.groupHeader,
                  {
                    backgroundColor: colors.surfaceMuted,
                    borderColor: colors.border,
                  },
                ]}>
                <Pressable
                  onPress={() => setIsConfiguracoesOpen((currentValue) => !currentValue)}
                  style={({ pressed }) => [
                    styles.groupMain,
                    pressed && styles.pressed,
                  ]}>
                  <View style={[styles.groupIcon, { backgroundColor: colors.primarySoft }]}>
                    <MaterialIcons name="settings" size={21} color={colors.primary} />
                  </View>
                  <View style={styles.groupText}>
                    <Text style={[styles.groupTitle, { color: colors.text }]}>Configurações</Text>
                    <Text style={[styles.groupMeta, { color: colors.textMuted }]}>
                      {CONFIGURACOES_ITEMS.length} funcionalidades
                    </Text>
                  </View>
                </Pressable>
                <Pressable
                  onPress={() => setIsConfiguracoesOpen((currentValue) => !currentValue)}
                  style={({ pressed }) => [
                    styles.collapseButton,
                    pressed && styles.pressed,
                  ]}>
                  <MaterialIcons
                    name={isConfiguracoesOpen ? 'keyboard-arrow-up' : 'keyboard-arrow-down'}
                    size={23}
                    color={colors.textMuted}
                  />
                </Pressable>
              </View>

              {isConfiguracoesOpen ? renderItems(CONFIGURACOES_ITEMS) : null}
            </View>
          </ScrollView>

          <View style={[styles.footer, { borderTopColor: colors.border }]}>
            <Pressable
              disabled={isLoggingOut}
              onPress={handleLogout}
              style={({ pressed }) => [
                styles.logoutButton,
                { backgroundColor: colors.dangerSoft },
                (pressed || isLoggingOut) && { backgroundColor: colors.dangerPressed },
              ]}>
              <View style={[styles.logoutIcon, { backgroundColor: colors.dangerPressed }]}>
                {isLoggingOut ? (
                  <ActivityIndicator color={colors.danger} />
                ) : (
                  <MaterialIcons name="logout" size={20} color={colors.danger} />
                )}
              </View>
              <Text style={[styles.logoutText, { color: colors.danger }]}>Sair</Text>
            </Pressable>
          </View>
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  overlay: {
    flex: 1,
    flexDirection: 'row',
    backgroundColor: '#102A4399',
  },
  backdrop: {
    ...StyleSheet.absoluteFillObject,
  },
  sidebar: {
    backgroundColor: '#FFFFFF',
    borderTopRightRadius: 8,
    borderBottomRightRadius: 8,
    overflow: 'hidden',
  },
  header: {
    minHeight: 78,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 11,
    borderBottomWidth: 1,
    borderBottomColor: '#D9E2EC',
    paddingHorizontal: 16,
    paddingVertical: 14,
  },
  brandIcon: {
    width: 42,
    height: 42,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
    backgroundColor: '#EAF4FB',
  },
  headerText: {
    flex: 1,
    gap: 2,
  },
  headerLabel: {
    color: '#627D98',
    fontSize: 12,
    fontWeight: '800',
    textTransform: 'uppercase',
  },
  headerTitle: {
    color: '#102A43',
    fontSize: 20,
    fontWeight: '800',
  },
  iconButton: {
    width: 38,
    height: 38,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#B6D4EA',
    backgroundColor: '#EAF4FB',
  },
  scroll: {
    flex: 1,
  },
  content: {
    gap: 14,
    padding: 14,
    paddingBottom: 24,
  },
  group: {
    gap: 10,
  },
  groupHeader: {
    minHeight: 58,
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#D9E2EC',
    backgroundColor: '#F8FAFC',
    overflow: 'hidden',
  },
  groupMain: {
    minHeight: 58,
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    padding: 10,
  },
  collapseButton: {
    width: 46,
    minHeight: 58,
    alignItems: 'center',
    justifyContent: 'center',
  },
  groupIcon: {
    width: 38,
    height: 38,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
    backgroundColor: '#EAF4FB',
  },
  groupText: {
    flex: 1,
    gap: 2,
  },
  groupTitle: {
    color: '#102A43',
    fontSize: 15,
    fontWeight: '800',
  },
  groupMeta: {
    color: '#627D98',
    fontSize: 12,
    fontWeight: '700',
  },
  items: {
    gap: 8,
  },
  item: {
    minHeight: 68,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    borderRadius: 8,
    backgroundColor: '#F8FAFC',
    padding: 10,
  },
  itemActive: {
    backgroundColor: '#1E4E79',
  },
  itemPressed: {
    backgroundColor: '#EAF4FB',
  },
  itemIcon: {
    width: 36,
    height: 36,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
    backgroundColor: '#EAF4FB',
  },
  itemIconActive: {
    backgroundColor: '#FFFFFF22',
  },
  itemText: {
    flex: 1,
    gap: 2,
  },
  itemTitle: {
    color: '#102A43',
    fontSize: 14,
    fontWeight: '800',
  },
  itemTitleActive: {
    color: '#FFFFFF',
  },
  itemDescription: {
    color: '#627D98',
    fontSize: 12,
    lineHeight: 16,
    fontWeight: '700',
  },
  itemDescriptionActive: {
    color: '#D9E8F5',
  },
  pressed: {
    opacity: 0.72,
  },
  footer: {
    borderTopWidth: 1,
    borderTopColor: '#D9E2EC',
    padding: 14,
  },
  logoutButton: {
    minHeight: 52,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    borderRadius: 8,
    backgroundColor: '#FFF5F5',
    paddingHorizontal: 10,
  },
  logoutButtonPressed: {
    backgroundColor: '#FFE3E3',
  },
  logoutIcon: {
    width: 34,
    height: 34,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
    backgroundColor: '#FFE3E3',
  },
  logoutText: {
    color: '#C53030',
    fontSize: 14,
    fontWeight: '800',
  },
});
