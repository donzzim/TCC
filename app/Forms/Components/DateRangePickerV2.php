<?php

namespace App\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;

class DateRangePickerV2 extends Field
{
    protected string $view = 'forms.components.date-range-pickerV2';

    protected array | Closure $agendas = [];

    protected array | Closure $agendaables = [];

    protected string $agendaableField = 'agendaable';

    public function agendas(array | Closure $agendas): static
    {
        $this->agendas = $agendas;

        return $this;
    }

    public function agendaables(array | Closure $agendaables): static
    {
        $this->agendaables = $agendaables;

        return $this;
    }

    public function agendaableField(string $field): static
    {
        $this->agendaableField = $field;

        return $this;
    }

    public function getAgendas(): array
    {
        return $this->evaluate($this->agendas) ?? [];
    }

    public function getAgendaables(): array
    {
        return $this->evaluate($this->agendaables) ?? [];
    }

    public function getAgendaableField(): string
    {
        return $this->agendaableField;
    }
}
