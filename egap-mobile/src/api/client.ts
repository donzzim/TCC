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

const TEMPO_LIMITE_MS = 15_000;

const VIA_NGROK =/\.ngrok(-free)?\.(app|dev|io)\b/.test(ENV.API_URL);

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
    // O ngrok gratuito troca a resposta por uma página de aviso quando a chamada vem de navegador.
    if (VIA_NGROK) {
        headers['ngrok-skip-browser-warning'] = '1';
    }

    let response: Response;
    // sem limite, uma requisição presa (sinal fraco) deixava o botão girando para sempre
    const controle = new AbortController();
    const limite = setTimeout(() => controle.abort(), TEMPO_LIMITE_MS);

    try {
        response = await fetch(buildUrl(path, init.query), {
            method: init.method ?? 'GET',
            headers,
            body: init.body !== undefined ? JSON.stringify(init.body) : undefined,
            signal: controle.signal,
        });
    } catch {
        // status 0 = sem resposta (rede ou tempo esgotado): quem chama pode tentar de novo
        throw new ApiError(
            0,
            controle.signal.aborted ? 'O servidor demorou para responder.' : 'Não foi possível conectar ao servidor.',
        );
    } finally {
        clearTimeout(limite);
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
