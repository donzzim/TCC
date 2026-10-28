import Svg, { Circle, G, Path, Rect } from 'react-native-svg';

interface Props {
    size?: number;
    /** Cor do que fica por trás: o "E", o furo e o anel da engrenagem são pintados com ela. */
    fundo?: string;
    selo?: string;
    engrenagem?: string;
}

// Mesmo desenho de assets/images/logo/egap-simbolo.svg, sem máscara (Mask falha no react-native-svg web).
const ENGRENAGEM =
    'M-3.46 -20.21 L-2.45 -25.88 L2.45 -25.88 L3.46 -20.21 A20.5 20.5 0 0 1 9.08 -18.38 L13.24 -22.38 L17.19 -19.50 ' +
    'L14.68 -14.31 A20.5 20.5 0 0 1 18.15 -9.54 L23.86 -10.33 L25.37 -5.67 L20.29 -2.95 A20.5 20.5 0 0 1 20.29 2.95 ' +
    'L25.37 5.67 L23.86 10.33 L18.15 9.54 A20.5 20.5 0 0 1 14.68 14.31 L17.19 19.50 L13.24 22.38 L9.08 18.38 ' +
    'A20.5 20.5 0 0 1 3.46 20.21 L2.45 25.88 L-2.45 25.88 L-3.46 20.21 A20.5 20.5 0 0 1 -9.08 18.38 L-13.24 22.38 ' +
    'L-17.19 19.50 L-14.68 14.31 A20.5 20.5 0 0 1 -18.15 9.54 L-23.86 10.33 L-25.37 5.67 L-20.29 2.95 ' +
    'A20.5 20.5 0 0 1 -20.29 -2.95 L-25.37 -5.67 L-23.86 -10.33 L-18.15 -9.54 A20.5 20.5 0 0 1 -14.68 -14.31 ' +
    'L-17.19 -19.50 L-13.24 -22.38 L-9.08 -18.38 A20.5 20.5 0 0 1 -3.46 -20.21 Z';

export function LogoEgap({ size = 40, fundo = '#ffffff', selo = '#2563eb', engrenagem = '#d97706' }: Props) {
    return (
        <Svg width={size} height={size} viewBox="-4 -4 132 132" accessibilityLabel="EGAP">
            <Rect x={2} y={2} width={108} height={108} rx={30} fill={selo} />
            <G transform="translate(44 26) skewX(-14)" fill={fundo}>
                <Rect x={0} y={0} width={14} height={60} rx={4} />
                <Rect x={0} y={0} width={46} height={13} rx={4} />
                <Rect x={0} y={23.5} width={34} height={13} rx={4} />
                <Rect x={0} y={47} width={46} height={13} rx={4} />
            </G>
            <Circle cx={25} cy={25} r={8} fill={fundo} />
            <Circle cx={96} cy={96} r={32} fill={fundo} />
            <Path d={ENGRENAGEM} transform="translate(96 96)" fill={engrenagem} />
            <Circle cx={96} cy={96} r={8} fill={fundo} />
        </Svg>
    );
}
