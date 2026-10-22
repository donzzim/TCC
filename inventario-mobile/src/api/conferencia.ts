import { apiClient } from './client';
import type { BemPatrimonial } from './bens';

export type ConferenciaStatus =
  | 'pendente'
  | 'localizado'
  | 'nao_localizado'
  | 'divergente'
  | 'em_transferencia'
  | 'cadastrado_manualmente'
  | 'registrado';

export type ResultadoLeituraStatus =
  | 'localizavel'
  | 'ja_conferido'
  | 'outro_setor'
  | 'nao_cadastrado'
  | 'situacao_nao_conferivel'
  | 'em_transferencia'
  | 'cadastrado_manualmente';

export interface ConferenciaInventario {
  id: number;
  numero: number | string | null;
  ano: number | string | null;
  situacao: string;
  situacao_raw?: number | string | null;
  inicio?: string | null;
  termino?: string | null;
}

export interface ConferenciaAtividade {
  id: number | null;
  unidade_judiciaria: number | string | null;
  setor: number | string | null;
  situacao: string;
  pode_editar: boolean;
  pode_finalizar: boolean;
  qtde_inventariada?: number | string | null;
  inicio?: string | null;
  termino?: string | null;
}

export interface ConferenciaResumo {
  total: number;
  localizados: number;
  pendentes: number;
  nao_localizados: number;
  divergentes: number;
  outro_setor: number;
  em_transferencia: number;
  cadastrados_manualmente: number;
  pode_finalizar: boolean;
}

export interface ConferenciaInfo {
  inventario: ConferenciaInventario;
  atividade: ConferenciaAtividade;
  resumo: ConferenciaResumo;
  scope?: {
    user_id: number;
    id_egap: number;
    setor: number;
    unidade_judiciaria: number;
  };
}

export interface BemConferencia extends BemPatrimonial {
  conferencia?: {
    status: ConferenciaStatus;
    status_label: string;
    id_inventario?: number | string | null;
    ja_registrado: boolean;
    item_id?: number | string | null;
    situacao_item?: string | null;
    observacao_item?: string | null;
  };
}

export interface ConferenciaBensResult extends ConferenciaInfo {
  total: number;
  bens: BemConferencia[];
  meta: ConferenciaPaginationMeta;
}

export interface ConferenciaPaginationMeta {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
  from: number | null;
  to: number | null;
  has_more: boolean;
}

export interface ConferenciaBensListParams {
  page?: number;
  perPage?: number;
  status?: 'todos' | ConferenciaStatus;
}

export interface ResultadoLeitura {
  status: ResultadoLeituraStatus;
  message: string;
  pode_localizar: boolean;
  bem: BemConferencia | null;
}

export interface AcaoConferenciaResult {
  status?: string;
  message: string;
  bem?: BemConferencia | null;
  bens?: BemConferencia[];
  resumo?: ConferenciaResumo;
  inventario?: ConferenciaInventario;
  atividade?: ConferenciaAtividade;
}

type ConferenciaBensApiResponse = Omit<ConferenciaBensResult, 'meta'> & {
  meta?: Partial<ConferenciaPaginationMeta>;
};

function buildBensEndpoint(params: ConferenciaBensListParams = {}): string {
  const query = new URLSearchParams();

  query.set('page', String(params.page ?? 1));
  query.set('per_page', String(params.perPage ?? 30));

  if (params.status && params.status !== 'todos') {
    query.set('status', params.status);
  }

  return `/conferencia/bens?${query.toString()}`;
}

function normalizeBensResponse(response: ConferenciaBensApiResponse): ConferenciaBensResult {
  const bens = response.bens ?? [];
  const total = response.total ?? response.meta?.total ?? bens.length;
  const currentPage = response.meta?.current_page ?? 1;
  const perPage = response.meta?.per_page ?? bens.length;
  const lastPage = response.meta?.last_page ?? currentPage;

  return {
    ...response,
    bens,
    total,
    meta: {
      current_page: currentPage,
      per_page: perPage,
      total,
      last_page: lastPage,
      from: response.meta?.from ?? (bens.length > 0 ? 1 : null),
      to: response.meta?.to ?? (bens.length > 0 ? bens.length : null),
      has_more: response.meta?.has_more ?? currentPage < lastPage,
    },
  };
}

export const conferenciaApi = {
  async atual(): Promise<ConferenciaInfo> {
    const { data } = await apiClient.get<ConferenciaInfo>('/conferencia/atual');

    return data;
  },

  async listarBens(params: ConferenciaBensListParams = {}): Promise<ConferenciaBensResult> {
    const { data } = await apiClient.get<ConferenciaBensApiResponse>(buildBensEndpoint(params));

    return normalizeBensResponse(data);
  },

  async validarLeitura(codigo: string): Promise<ResultadoLeitura> {
    const { data } = await apiClient.post<ResultadoLeitura>('/conferencia/validar-leitura', { codigo });

    return data;
  },

  async localizar(payload: { bem_id?: number | string; codigo?: string }): Promise<AcaoConferenciaResult> {
    const { data } = await apiClient.post<AcaoConferenciaResult>('/conferencia/localizar', payload);

    return data;
  },

  async registrarNaoLocalizado(bens: (number | string)[], justificativa: string): Promise<AcaoConferenciaResult> {
    const { data } = await apiClient.post<AcaoConferenciaResult>('/conferencia/nao-localizados', {
      bens,
      justificativa,
    });

    return data;
  },

  async registrarDivergencia(payload: {
    bem_id?: number | string;
    codigo?: string;
    campos?: string[];
    observacao: string;
  }): Promise<AcaoConferenciaResult> {
    const { data } = await apiClient.post<AcaoConferenciaResult>('/conferencia/divergencias', payload);

    return data;
  },

  async finalizar(): Promise<AcaoConferenciaResult> {
    const { data } = await apiClient.post<AcaoConferenciaResult>('/conferencia/finalizar', {});

    return data;
  },
};
