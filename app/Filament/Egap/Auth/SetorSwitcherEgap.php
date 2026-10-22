<?php

namespace App\Filament\Egap\Auth;

use Filament\Forms\Components\Select;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class SetorSwitcherEgap extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(['setorAtual' => EgapSession::setorAtual()]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('setorAtual')
                    ->hiddenLabel()
                    ->placeholder('Nenhum setor atribuído')
                    ->options(fn (): array => EgapSession::lotacoes()->pluck('setor', 'id')->all())
                    ->native(false)
                    ->selectablePlaceholder(false)
                    ->live()
                    ->afterStateUpdated(fn ($state) => $this->trocarSetor(filled($state) ? (int) $state : null)),
            ])
            ->statePath('data');
    }

    protected function trocarSetor(?int $setor): void
    {
        Session::put(EgapSession::SESSION_KEY_SETOR, $setor);

        EgapSession::setorAtual();

        $this->redirect(request()->header('Referer') ?? filament()->getUrl());
    }

    public function render(): View
    {
        return view('egap.navigation-bar.setor-switcher');
    }
}
