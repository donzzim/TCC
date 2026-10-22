import { apiClient } from './client';
import type { ConferenciaInfo } from './conferencia';

export interface DashboardSituacao {
  id: number | string | null;
  label: string;
  total: number;
}

export interface DashboardFinanceiro {
  valor_aquisicao: number;
  valor_atual: number;
  sem_valor: number;
  avaliados: number;
}

export interface DashboardResponse {
  bens: {
    total: number;
    situacoes: DashboardSituacao[];
  };
  conferencia: ConferenciaInfo | null;
  financeiro: DashboardFinanceiro;
}

export const dashboardApi = {
  async get(): Promise<DashboardResponse> {
    const { data } = await apiClient.get<DashboardResponse>('/dashboard');

    return data;
  },
};
