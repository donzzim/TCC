<?php

namespace App\Filament\App\Widgets;

use App\Models\Escola\Cursos;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExternosWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected ?string $pollingInterval = '30s';

    // Ocupa apenas 1 coluna lateral
    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    protected function getCards(): array
    {
        return Cursos::query()
            ->where('externo', true)
            ->where('ativo', true)
            ->whereDoesntHave('certMorph', function ($query) {
                $query->whereIn('validado', [config('settings.validacao.aprovado'), config('settings.validacao.aguardando')])
                    ->where('pessoa_id', Filament::auth()->id());
            })
            ->with('instituicao')
            ->get()
            ->map(function (Cursos $curso) {
                return Stat::make('Ação Pendente', $curso->titulo)
                    ->description($curso->instituicao->nome ?? 'Anexar Certificado')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('danger') // Vermelho atrai a atenção para a pendência
                    ->extraAttributes([
                        'class' => 'cursor-pointer hover:bg-stone-50 dark:hover:bg-stone-800/50 transition-colors ring-1 ring-danger-500/20',
                        'wire:click' => "\$dispatch('abrir-certificado-externo', { cursoId: {$curso->id} })",
                    ]);
            })
            ->all();
    }

    protected function getColumns(): int
    {
        return 1; // Força os cards a empilharem verticalmente
    }
}
