<?php

namespace App\Filament\Egap\Externo\Groups\Agendamento\Concerns;

use App\Models\egap\Agendamento\Solicitacao;
use Illuminate\Support\Carbon;

/**
 * Reúne a leitura/formatação de uma {@see Solicitacao} (agendamento de veículo)
 * compartilhada entre a tabela de agendamentos ({@see \App\Filament\Egap\Externo\Groups\Agendamento\Components\AgendamentoTable})
 * e o widget de calendário ({@see \App\Filament\Egap\Externo\Groups\Agendamento\Widgets\AgendamentoCalendarWidget}),
 * para que as duas exibam exatamente o mesmo texto/cor por situação.
 */
trait FormataSolicitacao
{
    private function agendamentoTexto(Solicitacao $record): string
    {
        if (blank($record->date_time)) {
            return (string) $record->id;
        }

        return $record->id . '/' . Carbon::parse($record->date_time)->format('Y');
    }

    private function rotaTexto(Solicitacao $record): string
    {
        $destino = $record->local_destino;

        if (blank($destino)) {
            $destino = collect($this->justificativaDados($record)['destino'] ?? [])->implode(', ');
        }

        return collect([$record->local_saida, $destino])
            ->filter(fn(?string $valor): bool => filled($valor))
            ->implode(' → ') ?: '-';
    }

    private function justificativaDados(Solicitacao $record): array
    {
        $valor = $record->justificativa;

        return is_array($valor) ? $valor : [];
    }

    private function justificativaTexto(Solicitacao $record): string
    {
        return trim((string) ($this->justificativaDados($record)['justificativa'] ?? ''));
    }

    private function passageiroPrincipal(Solicitacao $record)
    {
        return collect($this->justificativaDados($record)['passageiros'] ?? [])->first();
    }

    private function contatos(Solicitacao $record): ?string
    {
        $dados = $this->justificativaDados($record);

        return collect([$dados['telefone'] ?? null, $dados['celular'] ?? null])
            ->filter(fn(?string $valor): bool => filled($valor))
            ->implode(' / ') ?: null;
    }

    private function motoristaTexto(Solicitacao $record): string
    {
        $dados = $this->justificativaDados($record);

        return collect([
            filled($dados['veiculo_motorista'] ?? null) ? "Motorista: {$dados['veiculo_motorista']}" : null,
            filled($dados['aguardar_destino'] ?? null) ? "Aguarda no destino: {$dados['aguardar_destino']}" : null,
        ])->filter()->implode(' • ');
    }

    private function tipoTexto(Solicitacao $record): string
    {
        return (int) $record->tipo === Solicitacao::TIPO_VEICULOS ? 'Veículo' : 'Carga';
    }

    private function intempestividadeTexto(Solicitacao $record): ?string
    {
        $dados = $this->justificativaDados($record);

        return $dados['justificativa_intempestividade'] ?? $dados['justifique'] ?? null;
    }

    private function motivoTexto(Solicitacao $record): ?string
    {
        if (blank($record->motivo_cancelamento)) {
            return null;
        }

        $rotulo = (int) $record->id_situacao === Solicitacao::SITUACAO_INVALIDADO
            ? 'Motivo da invalidação'
            : 'Motivo do cancelamento';

        return "{$rotulo}: {$record->motivo_cancelamento}";
    }

    private function statusDescricao(Solicitacao $record): ?string
    {
        $situacao = (int) $record->id_situacao;

        if (in_array($situacao, [Solicitacao::SITUACAO_CANCELADO, Solicitacao::SITUACAO_INVALIDADO], true)) {
            return $this->motivoTexto($record);
        }

        if ($situacao !== Solicitacao::SITUACAO_CONCLUIDO) {
            return $this->intempestividadeTexto($record);
        }

        return null;
    }

    private function formatDataHora(?string $data, ?string $hora): ?string
    {
        if (blank($data)) {
            return null;
        }

        return filled($hora)
            ? Carbon::parse("{$hora}")->format('d/m/Y H:i')
            : Carbon::parse($data)->format('d/m/Y');
    }

    private function statusColor(int $status): string
    {
        return match ($status) {
            Solicitacao::SITUACAO_CONCLUIDO => 'success',
            Solicitacao::SITUACAO_AGENDADO => 'info',
            Solicitacao::SITUACAO_EM_ANALISE => 'warning',
            Solicitacao::SITUACAO_CANCELADO, Solicitacao::SITUACAO_INVALIDADO => 'danger',
            default => 'gray',
        };
    }
}
