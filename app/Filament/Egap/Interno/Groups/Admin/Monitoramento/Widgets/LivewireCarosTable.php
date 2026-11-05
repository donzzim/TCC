<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Monitoramento\Widgets;

class LivewireCarosTable extends RotasLentasTable
{
    protected const TITULO = 'Componentes Livewire mais caros';

    protected const DESCRICAO = 'Atualizações de tabelas, widgets e formulários do Filament, agrupadas pelos componentes envolvidos.';

    protected const VAZIO = 'Nenhuma atualização Livewire registrada no período.';

    protected bool $somenteLivewire = true;

    protected int $limite = 30;
}
