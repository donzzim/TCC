import { api } from './client';
import type { Dashboard } from './types';

export const dashboardApi = {
    obter: () => api<Dashboard>('/dashboard'),
};
