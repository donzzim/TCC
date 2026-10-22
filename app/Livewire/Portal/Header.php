<?php

namespace App\Livewire\Portal;

use App\Models\Portal\CMSSiteSettings;
use App\Models\Portal\CMSTemplates;
use Livewire\Component;

class Header extends Component
{
    public ?CMSSiteSettings $settings;

    public function mount()
    {
        $this->settings = CMSSiteSettings::getSettings();
    }

    public function render()
    {
        $template = CMSTemplates::getActive()->view;
        $viewPath = "templates." . $template . ".livewire.header";
        return view($viewPath);
    }
}
