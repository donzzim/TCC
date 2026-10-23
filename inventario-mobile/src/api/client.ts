import { ENV } from '@/src/config/env';

export class ApiError extends Error {
    status: number;
    code?: string;
    errors?: Record<string, string[]>;

    constructor(status: number, message: string, code?: string, errors?: Record<string, string[]>) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.code = code;
        this.errors = errors;
    }
}

let token: string | null = null;
let unauthorizedHandler: (() => void) | null = null;

export function setAuthToken(value: string | null): void {
    token = value;
}

export function onUnauthorized(handler: (() => void) | null): void {
    unauthorizedHandler = handler;
}

export interface ApiInit {
    method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
    body?: unknown;
    query?: Record<string, string | number | boolean | null | undefined>;
}

function buildUrl(path: string, query?: ApiInit['query']): string {
    const params = new URLSearchParams();

    for (const [key, value] of Object.entries(query ?? {})) {
        if (value !== undefined && value !== null && value !== '') {
            params.set(key, String(value));
        }
    }

    const qs = params.toString();
    const base = ENV.API_URL.replace(/\/+$/, '');

    return `${base}${path.startsWith('/') ? path : `/${path}`}${qs ? `?${qs}` : ''}`;
}

export async function api<T>(path: string, init: ApiInit = {}): Promise<T> {
    const headers: Record<string, string> = { Accept: 'application/json' };

    if (token) {
        headers.Authorization = `Bearer ${token}`;
    }
    if (init.body !== undefined) {
        headers['Content-Type'] = 'application/json';
    }

    let response: Response;

    try {
        response = await fetch(buildUrl(path, init.query), {
            method: init.method ?? 'GET',
            headers,
            body: init.body !== undefined ? JSON.stringify(init.body) : undefined,
        });
    } catch {
        throw new ApiError(0, 'Não foi possível conectar ao servidor.');
    }

    const text = await response.text();
    let payload: { message?: string; code?: string; errors?: Record<string, string[]> } | null = null;

    try {
        payload = text ? JSON.parse(text) : null;
    } catch {
        payload = null;
    }

    if (!response.ok) {
        if (response.status === 401) {
            unauthorizedHandler?.();
        }

        throw new ApiError(
            response.status,
            payload?.message ?? (response.status >= 500 ? 'Erro no servidor.' : 'Erro na requisição.'),
            payload?.code,
            payload?.errors,
        );
    }

    return payload as T;
}
