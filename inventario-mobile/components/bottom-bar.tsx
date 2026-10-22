import MaterialIcons from '@expo/vector-icons/MaterialIcons';
import { router, usePathname, type Href } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import {
  setPatrimonioNavigationDirectionFromRoutes,
  type PatrimonioRoute,
} from '@/src/navigation/patrimonioNavigation';
import { useAppTheme } from '@/src/theme/appTheme';

interface BottomBarItem {
  href: PatrimonioRoute;
  label: string;
  icon: keyof typeof MaterialIcons.glyphMap;
}

const ITEMS: BottomBarItem[] = [
  {
    href: '/patrimonio/principal',
    label: 'Dashboard',
    icon: 'dashboard',
  },
  {
    href: '/patrimonio/bens',
    label: 'Bens',
    icon: 'inventory-2',
  },
  {
    href: '/patrimonio/conferencia',
    label: 'Conferência',
    icon: 'fact-check',
  },
];

export function BottomBar() {
  const { colors } = useAppTheme();
  const pathname = usePathname();

  function handleNavigate(href: BottomBarItem['href']) {
    if (pathname === href) {
      return;
    }

    setPatrimonioNavigationDirectionFromRoutes(pathname, href);
    router.replace(href as Href);
  }

  return (
    <View
      style={[
        styles.container,
        {
          backgroundColor: colors.surface,
          borderTopColor: colors.border,
        },
      ]}>
      {ITEMS.map((item) => {
        const isActive = pathname === item.href;

        return (
          <Pressable
            key={item.href}
            onPress={() => handleNavigate(item.href)}
            style={({ pressed }) => [
              styles.item,
              isActive && { backgroundColor: colors.primary },
              pressed && !isActive && { backgroundColor: colors.primarySoft },
            ]}>
            <MaterialIcons
              name={item.icon}
              size={22}
              color={isActive ? colors.primaryText : colors.primary}
            />
            <Text
              style={[
                styles.label,
                { color: colors.primary },
                isActive && { color: colors.primaryText },
              ]}>
              {item.label}
            </Text>
          </Pressable>
        );
      })}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    minHeight: 68,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    borderTopWidth: 1,
    borderTopColor: '#D9E2EC',
    backgroundColor: '#FFFFFF',
    paddingHorizontal: 12,
    paddingTop: 8,
    paddingBottom: 8,
  },
  item: {
    minHeight: 50,
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 3,
    borderRadius: 8,
  },
  itemActive: {
    backgroundColor: '#1E4E79',
  },
  itemPressed: {
    backgroundColor: '#EAF4FB',
  },
  label: {
    color: '#1E4E79',
    fontSize: 11,
    fontWeight: '800',
  },
  labelActive: {
    color: '#FFFFFF',
  },
});
