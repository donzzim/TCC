<?php

namespace App\Filament\Traits;

use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;

trait HasCursoEstatisticas
{
    /**
     * Formata "Quantidade (Porcentagem%)" com cores opcionais.
     */
    public function fmtStat($count, $total, $colorClass = null): string
    {
        $percent = $total > 0 ? number_format(($count / $total) * 100, 1) : 0;
        $text = "{$count} ({$percent}%)";

        if ($colorClass) {
            return "<span class='text-{$colorClass}-600 font-bold'>{$text}</span>";
        }
        return $text;
    }

    /**
     * Cria um Placeholder configurado para renderizar HTML
     */
    public function makeStatPlaceholder(string $name, string $label): Placeholder
    {
        return Placeholder::make($name)
            ->label($label)
            ->html();
    }

    /**
     * Calcula as estatísticas baseadas no registro (Curso ou Atividade).
     */
    public function getEstatisticasData($record): array
    {
        if (!$record) return [];

        // --- 1. ALUNOS (Inscrição) ---
        $alunosCollection = $record->alunos ?? collect();
        $totalAlunos = $alunosCollection->count();

        $idPreInscrito = config('settings.inscricao.preinscricao') ?? 1;
        $idInscrito    = config('settings.inscricao.inscrito') ?? 2;
        $idIndeferido  = config('settings.inscricao.indeferido') ?? 3;

        $preInscritos = $alunosCollection->where('situacaoinscricao_id', $idPreInscrito)->count();
        $inscritos    = $alunosCollection->where('situacaoinscricao_id', $idInscrito)->count();
        $indeferidos  = $alunosCollection->where('situacaoinscricao_id', $idIndeferido)->count();

        // --- 2. FREQUÊNCIA ---
        $frequenciasCollection = $record->frequencias ?? collect();
        $totalFrequencias = $frequenciasCollection->count();

        $idPresente = config('settings.frequencia.presente') ?? 2701;
        $idAusente  = config('settings.frequencia.ausente') ?? 2702;

        $presentes = $frequenciasCollection->where('situacaofrequencia_id', $idPresente)->count();
        $ausentes  = $frequenciasCollection->where('situacaofrequencia_id', $idAusente)->count();

        // --- 3. APROVAÇÃO ---
        $alunosEfetivos = $alunosCollection->where('situacaoinscricao_id', $idInscrito);
        $totalAvaliaveis = $alunosEfetivos->count();

        $aprovados = $alunosEfetivos->where('aprovado', true)->count();
        $reprovados = $alunosEfetivos->where('aprovado', false)->count();

        // --- 4. EXTRAS ---
        $certificadosEmitidos = $record->certificados ? $record->certificados->count() : 0;

        $avaliacoesRealizadas = 0;
        if (method_exists($record, 'avaliacao')) {
            $avaliacoesRealizadas = $record->avaliacao->count();
        }

        return [
            'stat_pre_inscritos' => $this->fmtStat($preInscritos, $totalAlunos),
            'stat_inscritos'     => $this->fmtStat($inscritos, $totalAlunos, 'primary'),
            'stat_indeferidos'   => $this->fmtStat($indeferidos, $totalAlunos, 'danger'),
            'stat_presentes'     => $this->fmtStat($presentes, $totalFrequencias, 'success'),
            'stat_ausentes'      => $this->fmtStat($ausentes, $totalFrequencias, 'danger'),
            'stat_aprovados'     => $this->fmtStat($aprovados, $totalAvaliaveis, 'success'),
            'stat_reprovados'    => $this->fmtStat($reprovados, $totalAvaliaveis, 'danger'),
            'stat_certificados'  => $this->fmtStat($certificadosEmitidos, $totalAvaliaveis),
            'stat_avaliacoes'    => $this->fmtStat($avaliacoesRealizadas, $totalAvaliaveis),
        ];
    }

    /**
     * Retorna o schema visual para as estatísticas.
     */
    public function getEstatisticasSchema(): array
    {
        return [
            Section::make('Indicadores e Estatísticas')
                ->collapsible()
                ->compact()
                ->schema([
                    Grid::make(3)->schema([
                        Section::make('Inscrições')
                            ->columnSpan(1)
                            ->compact()
                            ->schema([
                                $this->makeStatPlaceholder('stat_pre_inscritos', 'Pré-inscritos'),
                                $this->makeStatPlaceholder('stat_inscritos', 'Inscritos'),
                                $this->makeStatPlaceholder('stat_indeferidos', 'Indeferidos'),
                            ]),

                        Section::make('Performance')
                            ->columnSpan(1)
                            ->compact()
                            ->schema([
                                $this->makeStatPlaceholder('stat_presentes', 'Presenças'),
                                $this->makeStatPlaceholder('stat_ausentes', 'Ausências'),
                                $this->makeStatPlaceholder('stat_aprovados', 'Aprovados'),
                                $this->makeStatPlaceholder('stat_reprovados', 'Reprovados'),
                            ]),

                        Section::make('Fechamento')
                            ->columnSpan(1)
                            ->compact()
                            ->schema([
                                $this->makeStatPlaceholder('stat_certificados', 'Certificados'),
                                $this->makeStatPlaceholder('stat_avaliacoes', 'Avaliações'),
                            ]),
                    ]),
                ]),
        ];
    }
}
