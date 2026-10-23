import { api } from './client';
import type { LoginResponse, Sessao } from './types';

export const sessaoApi = {
    login: (login: string, password: string, dispositivo?: string) =>
        api<LoginResponse>('/login', { method: 'POST', body: { login, password, dispositivo } }),
    atual: () => api<Sessao>('/sessao'),
    trocarSetor: (setorId: number) =>
        api<Sessao>('/sessao/setor', { method: 'PUT', body: { setor_id: setorId } }),
    logout: () => api<unknown>('/logout', { method: 'POST' }),
};
