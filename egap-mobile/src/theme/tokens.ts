export const radius = {
    card: 18,
    pill: 999,
    sheet: 26,
    input: 14,
} as const;

export const spacing = { xs: 4, sm: 8, md: 12, lg: 16, xl: 24 } as const;

export const gradient = ['#1e3a8a', '#2563eb', '#0ea5e9'] as const;

export interface Palette {
    primary: string;
    gradient: readonly [string, string, string];
    success: string;
    warning: string;
    danger: string;
    background: string;
    card: string;
    text: string;
    textMuted: string;
    border: string;
    track: string;
    onPrimary: string;
}

export const palette: { light: Palette; dark: Palette } = {
    light: {
        primary: '#2563eb',
        gradient,
        success: '#16a34a',
        warning: '#d97706',
        danger: '#dc2626',
        background: '#f4f6fb',
        card: '#ffffff',
        text: '#0f172a',
        textMuted: '#64748b',
        border: '#e2e8f0',
        track: '#e5eaf5',
        onPrimary: '#ffffff',
    },
    dark: {
        primary: '#3b82f6',
        gradient,
        success: '#22c55e',
        warning: '#f59e0b',
        danger: '#ef4444',
        background: '#0b1220',
        card: '#151e30',
        text: '#f1f5f9',
        textMuted: '#94a3b8',
        border: '#25314a',
        track: '#2a3348',
        onPrimary: '#ffffff',
    },
};
