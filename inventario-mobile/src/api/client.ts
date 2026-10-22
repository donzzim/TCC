import { ENV } from '../config/env';
import { ApiError, NetworkError } from './errors';
import { notifyAppError } from '@/src/errors/appErrorEvents';
import { appStorage } from '@/src/storage/appStorage';

// ---------------------------------------------------------------------------
// Constantes
// ---------------------------------------------------------------------------

const TOKEN_KEY = 'auth_token';

// Header obrigatório para o ngrok não retornar página HTML de aviso
const NGROK_HEADER = { 'ngrok-skip-browser-warning': '1' };

// ---------------------------------------------------------------------------
// Gerenciamento de token
// ---------------------------------------------------------------------------

export const tokenStorage = {
    async get(): Promise<string | null> {
        return appStorage.getItem(TOKEN_KEY);
    },

    async set(token: string): Promise<void> {
        await appStorage.setItem(TOKEN_KEY, token);
    },

    async remove(): Promise<void> {
        await appStorage.deleteItem(TOKEN_KEY);
    },
};

// ---------------------------------------------------------------------------
// Tipos
// ---------------------------------------------------------------------------

type HttpMethod = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';

interface RequestOptions {
    method?: HttpMethod;
    body?: unknown;
    /** Se false, não inclui o token Authorization (ex: rota de login) */
    authenticated?: boolean;
}

interface ApiResponse<T = unknown> {
    status: number;
    data: T;
}

function getErrorMessage(data: unknown): string {
    if (typeof data === 'object' && data !== null && 'message' in data) {
        const message = data.message;

        if (typeof message === 'string' && message.trim() !== '') {
            return message;
        }
    }

    return 'Erro na requisição.';
}

// ---------------------------------------------------------------------------
// Função central de requisição
// ---------------------------------------------------------------------------

export async function request<T = unknown>(
    endpoint: string,
    options: RequestOptions = {},
): Promise<ApiResponse<T>> {
    const { method = 'GET', body, authenticated = true } = options;

    // Monta headers base
    const headers: Record<string, string> = {
        'Content-Type': 'application/json',
        'Accept':       'application/json',
        ...NGROK_HEADER,
    };

    // Adiciona token Bearer se necessário
    if (authenticated) {
        const token = await tokenStorage.get();
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
    }

    const url = `${ENV.API_URL}${endpoint}`;

    try {
        const response = await fetch(url, {
            method,
            headers,
            body: body !== undefined ? JSON.stringify(body) : undefined,
        });

        // Tenta converter o JSON mesmo em caso de erro HTTP
        let data: T;
        try {
            data = await response.json();
        } catch {
            data = {} as T;
        }

        if (!response.ok) {
            const message = getErrorMessage(data);

            if (response.status >= 500) {
                notifyAppError({
                    kind: 'server',
                    title: 'Falha no servidor',
                    message,
                    status: response.status,
                });
            }

            throw new ApiError(response.status, message, data);
        }

        return { status: response.status, data };

    } catch (err) {
        if (err instanceof ApiError) throw err;

        // Erro de rede (sem internet, servidor fora, ngrok expirado, etc.)
        const networkError = new NetworkError('Não foi possível conectar ao servidor. Verifique sua conexão.');

        notifyAppError({
            kind: 'network',
            title: 'Sem conexão com o servidor',
            message: networkError.message,
        });

        throw networkError;
    }
}

// ---------------------------------------------------------------------------
// Atalhos semânticos
// ---------------------------------------------------------------------------

export const apiClient = {
    get<T>(endpoint: string, authenticated = true) {
        return request<T>(endpoint, { method: 'GET', authenticated });
    },

    post<T>(endpoint: string, body: unknown, authenticated = true) {
        return request<T>(endpoint, { method: 'POST', body, authenticated });
    },

    put<T>(endpoint: string, body: unknown, authenticated = true) {
        return request<T>(endpoint, { method: 'PUT', body, authenticated });
    },

    patch<T>(endpoint: string, body: unknown, authenticated = true) {
        return request<T>(endpoint, { method: 'PATCH', body, authenticated });
    },

    delete<T>(endpoint: string, authenticated = true) {
        return request<T>(endpoint, { method: 'DELETE', authenticated });
    },
};
