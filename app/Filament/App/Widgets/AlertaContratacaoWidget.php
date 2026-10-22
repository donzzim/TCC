<?php

namespace App\Filament\App\Widgets;

use App\Models\Escola\Instrutores;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class AlertaContratacaoWidget extends Widget
{
    protected string $view = 'filament.app.widgets.alerta-contratacao-widget';

    protected int|string|array $columnSpan = 'full';

    public $contratacao;

    public function mount()
    {
        $this->contratacao = Instrutores::with('curso')
            ->where('instrutor_id', Filament::auth()->id())
            ->whereIn('status_documentacao', ['pendente_instrutor', 'devolvido'])
            ->first();
    }
}
