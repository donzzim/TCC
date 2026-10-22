export class ApiError extends Error {
    constructor(
        public readonly status: number,
        message: string,
        public readonly data?: unknown,
    ) {
        super(message);
        this.name = 'ApiError';
    }
}

export class NetworkError extends Error {
    constructor(message = 'Sem conexão com o servidor.') {
        super(message);
        this.name = 'NetworkError';
    }
}