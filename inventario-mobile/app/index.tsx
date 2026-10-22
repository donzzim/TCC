import { Text } from 'react-native';

import { Screen } from '@/src/components/Screen';
import { useTheme } from '@/src/theme/ThemeProvider';

// Placeholder: a tela de login é implementada em tarefa posterior.
export default function Index() {
    const { colors } = useTheme();

    return (
        <Screen>
            <Text style={{ color: colors.text, padding: 16 }}>E-Gap Mobile</Text>
        </Screen>
    );
}
