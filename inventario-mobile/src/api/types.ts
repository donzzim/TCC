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
    codigodauo: number | null;
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

export interface Referencia {
    id: number | null;
    nome: string | null;
}

/** Espelha app/Http/Resources/Egap/MobileApi/BemResource.php (GET /bens e GET /bens/{patrimonio}). */
export interface Bem {
    id: number;
    patrimonio: string | number | null;
    patrimonio_anterior: string | number | null;
    tombo_smarapd: string | number | null;
    num_tombo_smarapd: string | number | null;
    numero_serie: string | null;
    descricao: string | null;
    descricao_resumida: string | null;
    marca: string | null;
    modelo: string | null;
    tipo_bem: string | null;
    estado_conservacao: string | null;
    voltagem: string | null;
    situacao_id: number | null;
    situacao: string | null;
    situacao_elegivel: boolean;
    unidade_judiciaria: Referencia;
    setor: Referencia;
    complemento_setor: Referencia;
    andar_setor: string | number | null;
    /** decimal:2 no servidor: normalmente string ("1234.50"). */
    valor_aquisicao: string | number | null;
    valor: string | number | null;
    data_incorporacao: string | null;
    data_cadastro: string | null;
    data_baixa: string | null;
    processo_baixa: string | null;
    numero_processo: string | null;
    nota_empenho: string | null;
    nota_liquidacao: string | null;
    data_liquidacao: string | null;
    observacao: string | null;
    /** So vem em GET /bens/{patrimonio}. */
    pertence_ao_setor?: boolean;
}

/** Espelha ConferenciaBensService::bemToArray (conferencia: lista, leitura e acoes). */
export interface BemConferencia {
    id: number;
    patrimonio: string | number | null;
    patrimonio_anterior: string | number | null;
    tombo_smarapd: string | number | null;
    num_tombo_smarapd: string | number | null;
    numero_serie: string | null;
    descricao: string | null;
    descricao_resumida: string | null;
    descricao_detalhada: string | null;
    marca: string | null;
    modelo: string | null;
    estado_conservacao: string | null;
    situacao_bem_id: number | null;
    situacao: string | null;
    sit_inventario: number | string | null;
    id_inventario: number | null;
    unidade_judiciaria: Referencia;
    setor: Referencia;
    complemento_setor: Referencia;
    andar_setor: string | number | null;
    valor_aquisicao: string | number | null;
    valor: string | number | null;
    observacao: string | null;
    conferencia: {
        status: ConferenciaStatus;
        status_label: string;
        id_inventario: number | null;
        ja_registrado: boolean;
        item_id: number | null;
        situacao_item: string | null;
        observacao_item: string | null;
    };
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
    bem: BemConferencia | null;
}

/** Leitura que localizou (ou achou já conferido) o bem: resultado de `localizar`, sem `pode_localizar`. */
export interface LeituraLocalizada {
    status: 'localizado' | 'ja_conferido';
    message: string;
    bem: BemConferencia;
    resumo: ConferenciaResumo;
}

/** Leitura que não localizou: resultado de `validarLeitura` + `resumo`. */
export interface LeituraRejeitada {
    status: LeituraStatus;
    message: string;
    pode_localizar: false;
    bem: BemConferencia | null;
    resumo: ConferenciaResumo;
}

/** `POST /conferencia/leituras` devolve um dos dois formatos. */
export type LeituraConferencia = LeituraLocalizada | LeituraRejeitada;

export type ConferenciaFiltro = 'todos' | Exclude<ConferenciaStatus, 'registrado'>;

export interface ConferenciaBensPagina {
    inventario: ConferenciaAtual['inventario'];
    atividade: ConferenciaAtual['atividade'];
    total: number;
    bens: BemConferencia[];
    resumo: ConferenciaResumo;
    meta: PaginationMeta & { from: number | null; to: number | null };
}

export interface DivergenciaPayload {
    bem_id?: number;
    codigo?: string;
    campos?: string[];
    observacao: string;
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
