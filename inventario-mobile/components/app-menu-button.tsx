import MaterialIcons from '@expo/vector-icons/MaterialIcons';
import { useState } from 'react';
import { Pressable, StyleSheet } from 'react-native';
import { useAppTheme } from '@/src/theme/appTheme';
import { AppSidebar } from './app-sidebar';

export function AppMenuButton() {
  const { colors } = useAppTheme();
  const [isSidebarVisible, setIsSidebarVisible] = useState(false);

  return (
    <>
      <AppSidebar
        visible={isSidebarVisible}
        onClose={() => setIsSidebarVisible(false)}
      />
      <Pressable
        onPress={() => setIsSidebarVisible(true)}
        style={({ pressed }) => [
          styles.button,
          {
            backgroundColor: colors.primarySoft,
            borderColor: colors.borderAccent,
          },
          pressed && styles.buttonPressed,
        ]}>
        <MaterialIcons name="menu" size={23} color={colors.primary} />
      </Pressable>
    </>
  );
}

const styles = StyleSheet.create({
  button: {
    width: 42,
    height: 42,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#B6D4EA',
    backgroundColor: '#EAF4FB',
  },
  buttonPressed: {
    opacity: 0.72,
  },
});
