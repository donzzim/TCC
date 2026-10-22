<?php

namespace App\Filament\Widgets\Administrativo\Concerns;



trait InteractsWithEventoCalendarNavigation
{
    public function getViewModesDisponiveis(): array
    {
        return [
            ...($this->exibirModoCalendario ? ['grid', 'list'] : []),
            ...($this->exibirModoTarefas ? ['tasks'] : []),
        ];
    }

    protected function normalizarViewMode(?string $mode): string
    {
        $modos = $this->getViewModesDisponiveis();

        return in_array($mode, $modos, true)
            ? $mode
            : ($modos[0] ?? 'grid');
    }

    public function setViewMode($mode)
    {
        if (! in_array($mode, $this->getViewModesDisponiveis(), true)) {
            return;
        }

        $this->viewMode = $mode;

        if ($mode !== 'list') {
            $this->fecharEventoLista();
        }

        if ($mode === 'tasks') {
            if (! $this->taskListLoaded) {
                $this->loadTasks();
            }

            return;
        }

        $this->taskList = [];
        $this->taskListLoaded = false;

        if ($this->exibirModoCalendario) {
            $this->loadEvents();
        }
    }

    public function nextMonth(): void
    {
        $this->currentMonth = $this->resolveCurrentMonth()->addMonth();
        $this->loadEvents();
    }

    public function previousMonth(): void
    {
        $this->currentMonth = $this->resolveCurrentMonth()->subMonth();
        $this->loadEvents();
    }
    /**
     * Action para alternar o status do Registro Funcional (Concluir/Reabrir)
     */
    // --- ACTIONS ---
}
