<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;

class ButtonGroupRadio extends Field
{
    /**
     * Aponta para a nossa view Blade customizada que renderizará o HTML.
     */
    protected string $view = 'forms.components.button-group-radio';

    /**
     * Armazena as opções (botões) a serem exibidas.
     * @var array
     */
    public array $options = [];

    /**
     * Método para definir as opções, imitando o componente Radio padrão.
     *
     * @param array $options
     * @return static
     */
    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Retorna o array de opções para a view.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
