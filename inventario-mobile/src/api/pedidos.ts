import { apiClient } from './client';

export type PedidoTipo = 'consumo' | 'permanente';
export type TipoAtendimentoPermanente = 'adicao' | 'substituicao';

export interface ComplementoSetorPedido {
  id: number;
  descricao: string;
}

export interface MaterialPedido {
  id: number;
  tipo: PedidoTipo;
  descricao: string;
  descricao_resumida_id?: number | null;
  descricao_resumida?: string | null;
  unidade: string;
  preco_medio: number;
  quantidade_estoque: number | null;
  disponivel: boolean;
  imagem?: string | null;
}

export interface PedidoScope {
  user_id: number;
  id_egap: number;
  setor: number;
  unidade_judiciaria: number;
}

export interface PedidosContextoResponse {
  scope: PedidoScope;
  complementos: ComplementoSetorPedido[];
}

export interface PedidosPaginationMeta {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
  from: number | null;
  to: number | null;
  has_more: boolean;
}

export interface MateriaisPedidoResult {
  materiais: MaterialPedido[];
  meta: PedidosPaginationMeta;
}

export interface CriarPedidoItemPayload {
  material_id: number;
  quantidade: number;
  tipo_atendimento?: TipoAtendimentoPermanente;
  justificativa?: string;
  patrimonio_substituido?: string;
}

export interface CriarPedidoPayload {
  tipo: PedidoTipo;
  complemento_setor_id: number;
  justificativa?: string;
  itens: CriarPedidoItemPayload[];
}

export interface PedidoCriado {
  id: number;
  data: string | null;
  tipo: PedidoTipo;
  situacao: {
    id: number | string | null;
    descricao: string | null;
  };
  itens_total: number;
}

export interface CriarPedidoResponse {
  message: string;
  pedido: PedidoCriado;
}

function buildMateriaisEndpoint(tipo: PedidoTipo, page: number, perPage: number, search: string): string {
  const query = new URLSearchParams();

  query.set('tipo', tipo);
  query.set('page', String(page));
  query.set('per_page', String(perPage));

  const trimmedSearch = search.trim();

  if (trimmedSearch) {
    query.set('search', trimmedSearch);
  }

  return `/pedidos/materiais?${query.toString()}`;
}

export const pedidosApi = {
  async contexto(): Promise<PedidosContextoResponse> {
    const { data } = await apiClient.get<PedidosContextoResponse>('/pedidos/contexto');

    return data;
  },

  async materiais({
    tipo,
    page = 1,
    perPage = 30,
    search = '',
  }: {
    tipo: PedidoTipo;
    page?: number;
    perPage?: number;
    search?: string;
  }): Promise<MateriaisPedidoResult> {
    const { data } = await apiClient.get<MateriaisPedidoResult>(
      buildMateriaisEndpoint(tipo, page, perPage, search),
    );

    return data;
  },

  async criar(payload: CriarPedidoPayload): Promise<CriarPedidoResponse> {
    const { data } = await apiClient.post<CriarPedidoResponse>('/pedidos', payload);

    return data;
  },
};
