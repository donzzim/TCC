import { apiClient, tokenStorage } from './client';
import { appStorage } from '@/src/storage/appStorage';

const USER_KEY = 'auth_user';

export interface MobileUser {
    id: number;
    idEgap: number | null;
    login: string | null;
    name: string | null;
    email: string | null;
    unidade_judiciaria: number | string | null;
    unidade_judiciaria_nome?: string | null;
    setor: number | string | null;
    setor_nome?: string | null;
}

export interface AuthSession {
    token: string;
    user: MobileUser;
}

interface LoginResponse {
    message: string;
    user: MobileUser & {
        token: string;
    };
}

interface MeResponse {
    user: MobileUser;
}

async function setStoredUser(user: MobileUser): Promise<void> {
    await appStorage.setItem(USER_KEY, JSON.stringify(user));
}

async function getStoredUser(): Promise<MobileUser | null> {
    const rawUser = await appStorage.getItem(USER_KEY);

    if (!rawUser) {
        return null;
    }

    try {
        const user = JSON.parse(rawUser) as MobileUser & { token?: string };

        if ('token' in user) {
            delete user.token;
            await setStoredUser(user);
        }

        return user;
    } catch {
        await appStorage.deleteItem(USER_KEY);
        return null;
    }
}

async function clearStoredSession(): Promise<void> {
    await Promise.all([
        tokenStorage.remove(),
        appStorage.deleteItem(USER_KEY),
    ]);
}

export const authApi = {
    async login(login: string, password: string): Promise<AuthSession> {
        const { data } = await apiClient.post<LoginResponse>(
            '/login',
            { login, password },
            false,
        );

        const { token, ...user } = data.user;

        await Promise.all([
            tokenStorage.set(token),
            setStoredUser(user),
        ]);

        return {
            token,
            user,
        };
    },

    async getStoredSession(): Promise<AuthSession | null> {
        const [token, user] = await Promise.all([
            tokenStorage.get(),
            getStoredUser(),
        ]);

        if (!token || !user) {
            return null;
        }

        return { token, user };
    },

    async me(): Promise<MobileUser> {
        const { data } = await apiClient.get<MeResponse>('/me');

        await setStoredUser(data.user);

        return data.user;
    },

    async validateSession(): Promise<boolean> {
        try {
            await apiClient.get<MeResponse>('/me');
            return true;
        } catch {
            await clearStoredSession();
            return false;
        }
    },

    async logout(): Promise<void> {
        try {
            await apiClient.post('/logout', {});
        } finally {
            await clearStoredSession();
        }
    },

    clearStoredSession,
};
