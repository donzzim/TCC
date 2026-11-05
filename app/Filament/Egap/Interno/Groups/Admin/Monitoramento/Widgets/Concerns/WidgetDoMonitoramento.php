<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets\Concerns;

use App\Monitoramento\AcessoAoMonitoramento;
use App\Monitoramento\Armazenamento\RepositorioDeMetricas;
use App\Monitoramento\Armazenamento\RepositorioSeguro;
use App\Monitoramento\Leitura\Periodo;
use Livewire\Attributes\Reactive;

trait WidgetDoMonitoramento
{
    #[Reactive]
    public string $periodo = '1h';

    #[Reactive]
    public ?string $contexto = null;

    public function bootWidgetDoMonitoramento(): void
    {
        abort_unless(AcessoAoMonitoramento::permitido(auth()->user()), 403);
    }

    public static function canView(): bool
    {
        return AcessoAoMonitoramento::permitido(auth()->user());
    }

    protected function periodoAtual(): Periodo
    {
        return Periodo::tryFrom($this->periodo) ?? Periodo::H1;
    }

    protected function repositorio(): RepositorioDeMetricas
    {
        return new RepositorioSeguro(app(RepositorioDeMetricas::class));
    }

    protected function agora(): int
    {
        return now()->getTimestamp();
    }

    protected static function ms(?float $ms): string
    {
        if ($ms === null) {
            return '—';
        }

        if ($ms >= 10000) {
            return '> 10 s';
        }

        return $ms >= 1000
            ? number_format($ms / 1000, 1, ',', '.').' s'
            : number_format($ms, 0, ',', '.').' ms';
    }

    protected static function rotuloDoInstante(int $instante, bool $comData = false): string
    {
        return now()->setTimestamp($instante)->format($comData ? 'd/m H:i' : 'H:i');
    }
}
