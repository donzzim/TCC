import { Inter_400Regular } from '@expo-google-fonts/inter/400Regular';
import { Inter_500Medium } from '@expo-google-fonts/inter/500Medium';
import { Inter_600SemiBold } from '@expo-google-fonts/inter/600SemiBold';
import { Inter_700Bold } from '@expo-google-fonts/inter/700Bold';
import { Inter_800ExtraBold } from '@expo-google-fonts/inter/800ExtraBold';
import type { TextStyle } from 'react-native';

export const fontes = {
    Inter_400Regular,
    Inter_500Medium,
    Inter_600SemiBold,
    Inter_700Bold,
    Inter_800ExtraBold,
};

const POR_PESO: Record<string, keyof typeof fontes> = {
    '400': 'Inter_400Regular',
    normal: 'Inter_400Regular',
    '500': 'Inter_500Medium',
    '600': 'Inter_600SemiBold',
    '700': 'Inter_700Bold',
    bold: 'Inter_700Bold',
    '800': 'Inter_800ExtraBold',
    '900': 'Inter_800ExtraBold',
};

/**
 * Fontes customizadas no Android não resolvem `fontWeight` sozinhas: cada peso é
 * uma família própria. Converte o peso pedido na família Inter correspondente.
 */
export function familiaPorPeso(peso: TextStyle['fontWeight']): string {
    return POR_PESO[String(peso ?? '400')] ?? 'Inter_400Regular';
}
