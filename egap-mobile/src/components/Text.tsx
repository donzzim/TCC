import type { ComponentProps } from 'react';
import {
    Text as RNText,
    TextInput as RNTextInput,
    StyleSheet,
    type TextProps,
    type TextStyle,
} from 'react-native';

import { familiaPorPeso } from '@/src/theme/fonts';

function comFonte(style: TextProps['style']) {
    const { fontWeight, fontFamily, ...resto } = (StyleSheet.flatten(style) ?? {}) as TextStyle;
    return [resto, { fontFamily: fontFamily ?? familiaPorPeso(fontWeight) }];
}

/** `Text` do React Native com a fonte Inter no peso pedido em `fontWeight`. */
export function Text({ style, ...props }: TextProps) {
    return <RNText {...props} style={comFonte(style)} />;
}

/** `TextInput` do React Native com a fonte Inter no peso pedido em `fontWeight`. */
export function TextInput({ style, ...props }: ComponentProps<typeof RNTextInput>) {
    return <RNTextInput {...props} style={comFonte(style)} />;
}

