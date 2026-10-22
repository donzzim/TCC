export function moeda(n: number): string {
    return n.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

export function numero(n: number): string {
    return n.toLocaleString('pt-BR');
}

export function dataHora(iso: string | null | undefined): string {
    if (!iso) return '-';

    const d = new Date(iso);

    if (Number.isNaN(d.getTime())) return '-';

    return d.toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export function soDigitos(s: string): string {
    return s.replace(/\D+/g, '');
}
