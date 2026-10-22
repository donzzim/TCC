@if (auth(\App\Models\egap\User::GUARD)->user()?->acessaAreaExterna())
    @include('egap.navigation-bar.area-externa')
@endif
@livewire('setor-switcher-egap')
