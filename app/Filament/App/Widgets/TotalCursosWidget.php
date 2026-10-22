<?php

namespace App\Filament\App\Widgets;

use App\Models\Escola\CursoAlunos;
use App\Models\Escola\CursoCertificados;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalCursosWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '30s';

    // Ocupa todas as colunas do grid pai
    protected int|string|array $columnSpan = 'full';

    protected function getCards(): array
    {
        $userId = Filament::auth()->id();
        $cursosComoAluno = CursoAlunos::where('aluno_id', $userId)->count();
        $certificados = CursoCertificados::where('pessoa_id', $userId)->count();

        $cursosConcluidos = CursoAlunos::where('aluno_id', $userId)->where('aprovado', 1)->count();
        $cursosEmAndamento = max(0, $cursosComoAluno - $cursosConcluidos);

        return [
            Stat::make('Meus Cursos', $cursosComoAluno)
                ->description('Total de matrículas')
                ->icon('heroicon-o-academic-cap')
                ->color('primary'),

            Stat::make('Em Andamento', $cursosEmAndamento)
                ->description('Cursos ativos no momento')
                ->icon('heroicon-o-play-circle')
                ->color('warning'),

            Stat::make('Concluídos', $cursosConcluidos)
                ->description('Finalizados com sucesso')
                ->icon('heroicon-o-check-badge')
                ->color('success'),

            Stat::make('Certificados', $certificados)
                ->description('Emitidos ou validados')
                ->icon('heroicon-o-document-check')
                ->color('info'),
        ];
    }

    protected function getColumns(): int
    {
        return 4; // 4 cards na mesma linha em telas grandes
    }
}
