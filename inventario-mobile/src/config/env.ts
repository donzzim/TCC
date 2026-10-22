const apiUrl =
    process.env.EXPO_PUBLIC_API_URL ??
    process.env.EXPO_PUBLIC_EGAP_API_URL ?? // fallback legado
    '';

if (!apiUrl) {
    console.warn('[config] EXPO_PUBLIC_API_URL não definida. Verifique o .env.');
}

export const ENV = {
    API_URL:      apiUrl,
    USE_MOCK_API: process.env.EXPO_PUBLIC_USE_MOCK_API === 'true',
} as const;