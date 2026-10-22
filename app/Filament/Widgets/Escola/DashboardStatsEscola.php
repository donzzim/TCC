<?php

namespace App\Filament\Widgets\Escola;

use App\Models\Escola\Cursos;
use App\Models\Administrativo\Demanda;
use App\Models\Escola\CursoAlunos;
use App\Models\Escola\CursoCertificados;
use App\Models\User;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

class DashboardStatsEscola extends BaseWidget
{
    use HasWidgetShield;
    protected static ?int $sort = 1;
    protected ?string $pollingInterval = '100s';

    protected function getHeading(): ?string
    {
        return $this->heading;
    }

    public function gerarDados($tam)
    {
        $arr = [];
        for ($i = 0; $i < $tam; $i++) {
            $arr[] = fake()->numberBetween(0, 30);
        }
        return $arr;
    }

    protected function getStats(): array
    {
        $arrPessoas = $this->gerarDados(12);
        $arrAlunos = $this->gerarDados(12);
        $arrCursos = $this->gerarDados(12);
        $arrCertificados = $this->gerarDados(12);

        $totalPessoas = number_format(User::count(), 0, ',', '.');
        $totalCursos = number_format(Cursos::where('id', '>', 0)->count(), 0, ',', '.');
        $totalAlunos = number_format(CursoAlunos::count(), 0, ',', '.');
        $totalCertificados = number_format(CursoCertificados::count(), 0, ',', '.');

        return [
            Stat::make('Pessoas', $totalPessoas)
                ->description('total de pessoas cadastradas')
                ->descriptionIcon('heroicon-o-user')
                ->chart($arrPessoas)
                ->color('info')
                ->extraAttributes(['class' => 'bg-gradient-to-r dark:from-sky-900 dark:to-sky-0 from-sky-100 to-sky-0']),

            Stat::make('Cursos', $totalCursos)
                ->description('total de cursos ofertados')
                ->descriptionIcon('heroicon-o-book-open')
                ->chart($arrCursos)
                ->color('success')
                ->extraAttributes(['class' => 'bg-gradient-to-r dark:from-green-900 dark:to-green-0 from-green-100 to-green-0']),

            Stat::make('Alunos', $totalAlunos)
                ->description('total de alunos inscritos')
                ->descriptionIcon('heroicon-o-user-group')
                ->chart($arrAlunos)
                ->color('warning')
                ->extraAttributes(['class' => 'bg-gradient-to-r dark:from-orange-900 dark:to-orange-0 from-orange-100 to-orange-0']),

            Stat::make('Certificados', $totalCertificados)
                ->description('total de certificados emitidos')
                ->descriptionIcon('heroicon-o-academic-cap')
                ->chart($arrCertificados)
                ->color('primary')
                ->extraAttributes(['class' => 'bg-gradient-to-r dark:from-slate-900 dark:to-slate-0 from-slate-100 to-slate-0']),
        ];
    }
}
