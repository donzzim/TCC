<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Pages;

use App\Filament\Egap\Interno\Clusters\AdminEgapCluster;
use App\Monitoramento\AcessoAoMonitoramento;
use App\Monitoramento\Armazenamento\RepositorioDeMetricas;
use App\Monitoramento\Leitura\Periodo;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Url;

class Monitoramento extends Page
{
    public const ABAS = [
        'visao-geral' => 'Visão geral',
        'desempenho' => 'Desempenho',
        'banco' => 'Banco de dados',
        'filas' => 'Filas e agendador',
        'erros' => 'Erros e logs',
        'servidor' => 'Servidor',
    ];

    public const CONTEXTOS = [
        'admin' => 'Admin (EMES)',
        'app' => 'App (EMES)',
        'egap' => 'EGAP interno',
        'egap-externo' => 'EGAP externo',
        'mobile-api' => 'App mobile (API)',
        'outros' => 'Outros',
    ];

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static ?string $navigationLabel = 'Monitoramento';

    protected static ?string $title = 'Monitoramento';

    protected static ?string $slug = 'monitoramento';

    protected static ?int $navigationSort = 90;

    protected static ?string $cluster = AdminEgapCluster::class;

    protected string $view = 'egap.monitoramento.pagina';

    #[Url]
    public string $aba = 'visao-geral';

    #[Url]
    public string $periodo = '1h';

    #[Url]
    public ?string $contexto = null;

    public static function canAccess(): bool
    {
        return AcessoAoMonitoramento::permitido(auth()->user());
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $this->normalizarFiltros();
    }

    public function updated(): void
    {
        $this->normalizarFiltros();
    }

    public function ultimaColeta(): ?int
    {
        $snapshot = rescue(fn () => app(RepositorioDeMetricas::class)->ultimoSnapshot(), null, false);

        return $snapshot?->instante;
    }

    private function normalizarFiltros(): void
    {
        $this->aba = array_key_exists($this->aba, self::ABAS) ? $this->aba : 'visao-geral';
        $this->periodo = Periodo::tryFrom($this->periodo)?->value ?? Periodo::H1->value;
        $this->contexto = array_key_exists((string) $this->contexto, self::CONTEXTOS) ? $this->contexto : null;
    }
}
