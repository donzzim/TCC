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

/** "agora", "há 5 min", "há 3 h", "ontem", "há 4 dias" ou a data, para datas mais antigas. */
export function tempoRelativo(iso: string | null | undefined, agora: number = Date.now()): string {
    if (!iso) return '';

    const d = new Date(iso);

    if (Number.isNaN(d.getTime())) return '';

    const minutos = Math.floor((agora - d.getTime()) / 60_000);

    if (minutos < 1) return 'agora';
    if (minutos < 60) return `há ${minutos} min`;

    const horas = Math.floor(minutos / 60);

    if (horas < 24) return `há ${horas} h`;

    const dias = Math.floor(horas / 24);

    if (dias === 1) return 'ontem';
    if (dias < 7) return `há ${dias} dias`;

    return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
}
