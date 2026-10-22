import { useMemo } from 'react';
import { useAppTheme } from '@/src/theme/appTheme';

export function useThemeStyles() {
  const { colors, isDark, mode } = useAppTheme();

  const themed = useMemo(() => ({
    colors,
    isDark,
    mode,
    screen: {
      backgroundColor: colors.screen,
    },
    surface: {
      backgroundColor: colors.surface,
      borderColor: colors.border,
    },
    mutedSurface: {
      backgroundColor: colors.surfaceMuted,
      borderColor: colors.border,
    },
    accentSurface: {
      backgroundColor: colors.surfaceAccent,
      borderColor: colors.borderAccent,
    },
    primarySurface: {
      backgroundColor: colors.primarySoft,
      borderColor: colors.borderAccent,
    },
    input: {
      backgroundColor: colors.input,
      borderColor: colors.border,
      color: colors.text,
    },
    text: {
      color: colors.text,
    },
    mutedText: {
      color: colors.textMuted,
    },
    subtleText: {
      color: colors.textSubtle,
    },
    primaryText: {
      color: colors.primary,
    },
    onPrimaryText: {
      color: colors.primaryText,
    },
    overlay: {
      backgroundColor: colors.overlay,
    },
    successSurface: {
      backgroundColor: colors.successSoft,
      borderColor: colors.success,
    },
    dangerSurface: {
      backgroundColor: colors.dangerSoft,
      borderColor: colors.danger,
    },
  }), [colors, isDark, mode]);

  return themed;
}
