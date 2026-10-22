<header>
    @if ($settings && $settings->logo_path)
    <a href="{{ route('portal.home') }}">
        <img src="{{ Storage::url($settings->logo_path) }}" alt="{{ $settings->brand_name ?? 'Logo' }}">
    </a>
    @elseif ($settings)
    <h1><a href="{{ route('portal.home') }}">{{ $settings->brand_name }}</a></h1>
    @endif

    {{-- Inclui o componente de navegação aqui --}}
    <livewire:portal.navigation menuName="Principal" />
</header>