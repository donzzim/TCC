<?php

namespace App\Livewire\Portal;

use App\Models\Portal\CMSTemplates;
use App\Services\MenuBuilder;
use Illuminate\Support\Collection;
use Livewire\Component;

class Navigation extends Component
{
    public Collection $items;

    public function mount(string $menuName = 'Principal')
    {
        $this->items = (new MenuBuilder($menuName))->build();
    }

    public function render()
    {
        $template = CMSTemplates::getActive()->view;
        $viewPath = "templates." . $template . ".livewire.navigation";
        return view($viewPath);
    }
}
