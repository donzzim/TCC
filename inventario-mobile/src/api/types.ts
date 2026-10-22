export interface Usuario {
    id: number;
    nome: string;
    login: string;
    email: string | null;
    cpf: string | null;
    cargo: string | null;
}

export interface Lotacao {
    id: number;
    setor: string;
    unidade: string;
    codigodauo: number;
    principal?: boolean;
}

export interface Sessao {
    usuario: Usuario;
    egap_id: number | null;
    setor_atual: Lotacao | null;
    lotacoes: Lotacao[];
    lotacao_legada: boolean;
}

export interface LoginResponse {
    token: string;
    sessao: Sessao;
}

export interface PaginationMeta {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
    has_more: boolean;
}

export interface Paginated<T> {
    data: T[];
    meta: PaginationMeta;
}

export type ConferenciaStatus =
    | 'pendente'
    | 'localizado'
    | 'nao_localizado'
    | 'divergente'
    | 'em_transferencia'
    | 'cadastrado_manualmente'
    | 'registrado';

export interface Bem {
    id: number;
    patrimonio?: string | null;
    codigo?: string | null;
    descricao?: string | null;
    marca?: string | null;
    modelo?: string | null;
    numero_serie?: string | null;
    situacao?: string | null;
    estado_conservacao?: string | null;
    setor?: string | null;
    unidade_judiciaria?: string | null;
    localizacao?: string | null;
    responsavel?: string | null;
    valor_aquisicao?: number | null;
    data_incorporacao?: string | null;
    pertence_ao_setor?: boolean;
    situacao_elegivel?: boolean;
    conferencia?: {
        status: ConferenciaStatus;
        status_label: string;
        ja_registrado: boolean;
    };
    [key: string]: unknown;
}

export interface DashboardSituacao {
    id: number | string | null;
    label: string;
    total: number;
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

export interface ConferenciaAtual {
    inventario: {
        id: number;
        numero: number | string | null;
        ano: number | string | null;
        situacao: string;
        inicio?: string | null;
        termino?: string | null;
    };
    atividade: {
        id: number | null;
        situacao: string;
        pode_editar: boolean;
        pode_finalizar: boolean;
        inicio?: string | null;
        termino?: string | null;
    };
    resumo: ConferenciaResumo;
}

export interface Dashboard {
    bens: { total: number; situacoes: DashboardSituacao[] };
    financeiro: { valor_aquisicao: number; valor_atual: number; sem_valor: number };
    conferencia: ConferenciaAtual | null;
    pedidos_abertos: number;
}

export type LeituraStatus =
    | 'localizavel'
    | 'ja_conferido'
    | 'outro_setor'
    | 'nao_cadastrado'
    | 'situacao_nao_conferivel'
    | 'em_transferencia'
    | 'cadastrado_manualmente';

export interface LeituraResultado {
    status: LeituraStatus;
    message: string;
    pode_localizar?: boolean;
    bem: Bem | null;
}

export type PedidoTipo = 'consumo' | 'permanente';

export interface Material {
    id: number;
    tipo: PedidoTipo;
    descricao: string;
    descricao_resumida?: string | null;
    unidade: string;
    preco_medio: number;
    quantidade_estoque: number | null;
    disponivel: boolean;
    imagem?: string | null;
}

export interface Pedido {
    id: number;
    data: string | null;
    tipo: PedidoTipo;
    situacao: { id: number | string | null; descricao: string | null };
    itens_total: number;
}
