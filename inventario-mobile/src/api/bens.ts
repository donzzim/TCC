import { apiClient } from './client';

interface BemReferencia {
    id: number | string | null;
    nome: string | null;
}

export interface BemPatrimonial {
    id: number | string;
    codigo?: string | null;
    patrimonio?: string | null;
    codigo_patrimonial?: string | null;
    tombamento?: string | null;
    tombo_smarapd?: string | null;
    num_tombo_smarapd?: string | null;
    patrimonio_anterior?: string | null;
    numero_serie?: string | null;
    descricao?: string | null;
    descricao_resumida?: string | null;
    denominacao?: string | null;
    marca?: string | null;
    modelo?: string | null;
    serie?: string | null;
    tipo_bem?: string | null;
    estado_conservacao?: string | null;
    voltagem?: string | null;
    situacao?: string | null;
    estado?: string | null;
    unidade_judiciaria?: BemReferencia | number | string | null;
    setor?: BemReferencia | number | string | null;
    complemento_setor?: BemReferencia | number | string | null;
    andar_setor?: number | string | null;
    localizacao?: string | null;
    responsavel?: string | null;
    valor_aquisicao?: number | string | null;
    valor?: number | string | null;
    data_incorporacao?: string | null;
    data_cadastro?: string | null;
    data_baixa?: string | null;
    processo_baixa?: string | null;
    numero_processo?: string | null;
    nota_empenho?: string | null;
    nota_liquidacao?: string | null;
    data_liquidacao?: string | null;
    observacao?: string | null;
    [key: string]: unknown;
}

export interface BensSetorResult {
    bens: BemPatrimonial[];
    total: number;
    meta: BensPaginationMeta;
}

export interface BensPaginationMeta {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
    from: number | null;
    to: number | null;
    has_more: boolean;
}

export interface BensListParams {
    page?: number;
    perPage?: number;
    search?: string;
}

type BensApiResponse =
    | {
        bens?: BemPatrimonial[];
        data?: BemPatrimonial[];
        total?: number;
        meta?: {
            current_page?: number;
            per_page?: number;
            total?: number;
            last_page?: number;
            from?: number | null;
            to?: number | null;
            has_more?: boolean;
        };
    }
    | BemPatrimonial[];

interface BemConsultaEnvelope {
    bem?: BemPatrimonial | null;
    data?: BemPatrimonial | BemPatrimonial[] | null;
    bens?: BemPatrimonial[];
}

type BemConsultaApiResponse = BemConsultaEnvelope | BemPatrimonial | BemPatrimonial[];

function getBemCodeCandidates(bem: BemPatrimonial): string[] {
    return [
        bem.codigo,
        bem.patrimonio,
        bem.codigo_patrimonial,
        bem.tombamento,
        bem.tombo_smarapd,
        bem.num_tombo_smarapd,
        bem.id,
    ]
        .filter((value) => value !== null && value !== undefined && value !== '')
        .map(String);
}

function isSamePatrimonio(bem: BemPatrimonial, patrimonio: string): boolean {
    const normalizedPatrimonio = patrimonio.trim();
    const normalizedPatrimonioWithoutZeros = normalizedPatrimonio.replace(/^0+/, '') || '0';

    return getBemCodeCandidates(bem).some((candidate) => {
        const normalizedCandidate = candidate.trim();
        const normalizedCandidateWithoutZeros = normalizedCandidate.replace(/^0+/, '') || '0';

        return normalizedCandidate === normalizedPatrimonio
            || normalizedCandidateWithoutZeros === normalizedPatrimonioWithoutZeros;
    });
}

function isObject(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null;
}

function isBemPatrimonial(value: unknown): value is BemPatrimonial {
    return isObject(value) && 'id' in value;
}

function isBemConsultaEnvelope(value: unknown): value is BemConsultaEnvelope {
    return isObject(value) && ('bem' in value || 'data' in value || 'bens' in value);
}

function normalizeBensResponse(response: BensApiResponse): BensSetorResult {
    if (Array.isArray(response)) {
        return {
            bens: response,
            total: response.length,
            meta: {
                current_page: 1,
                per_page: response.length,
                total: response.length,
                last_page: 1,
                from: response.length > 0 ? 1 : null,
                to: response.length > 0 ? response.length : null,
                has_more: false,
            },
        };
    }

    const bens = response.bens ?? response.data ?? [];
    const total = response.total ?? response.meta?.total ?? bens.length;
    const currentPage = response.meta?.current_page ?? 1;
    const perPage = response.meta?.per_page ?? bens.length;
    const lastPage = response.meta?.last_page ?? currentPage;

    return {
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

function buildBensEndpoint(params: BensListParams = {}): string {
    const query = new URLSearchParams();

    query.set('page', String(params.page ?? 1));
    query.set('per_page', String(params.perPage ?? 30));

    const search = params.search?.trim();

    if (search) {
        query.set('search', search);
    }

    return `/bens?${query.toString()}`;
}

function normalizeBemConsultaResponse(
    response: BemConsultaApiResponse,
    patrimonio: string,
): BemPatrimonial | null {
    if (Array.isArray(response)) {
        return response.find((bem) => isSamePatrimonio(bem, patrimonio)) ?? null;
    }

    if (isBemConsultaEnvelope(response)) {
        if (response.bem !== undefined) {
            return response.bem;
        }

        if (response.data !== undefined) {
            if (Array.isArray(response.data)) {
                return response.data.find((bem) => isSamePatrimonio(bem, patrimonio)) ?? null;
            }

            return response.data;
        }

        if (response.bens !== undefined) {
            return response.bens.find((bem) => isSamePatrimonio(bem, patrimonio)) ?? null;
        }
    }

    return isBemPatrimonial(response) ? response : null;
}

export const bensApi = {
    async listByUserSector(params: BensListParams = {}): Promise<BensSetorResult> {
        const { data } = await apiClient.get<BensApiResponse>(buildBensEndpoint(params));

        return normalizeBensResponse(data);
    },

    async consultByPatrimonio(patrimonio: string): Promise<BemPatrimonial | null> {
        const encodedPatrimonio = encodeURIComponent(patrimonio.trim());
        const { data } = await apiClient.get<BemConsultaApiResponse>(`/bens/${encodedPatrimonio}`);

        return normalizeBemConsultaResponse(data, patrimonio);
    },
};
