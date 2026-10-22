<div class="fi-page-content-wrapper">
    @vite(['resources/css/filament/admin/theme.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/mask@3.x.x/dist/cdn.min.js"></script>

    <div class="flex flex-col items-center justify-center p-4 md:p-8 xl:p-12 bg-sky-50">
        <div class="flex flex-row items-center justify-center mb-2 text-center md:flex-row">
            <div class="mb-4 header-logo-img md:mb-0 md:mr-4">
                <img src="{{ asset('images/logo.png') }}" class="h-16" alt="Logo da Escola da Magistratura" />
            </div>

            <div class="ml-0 text-center md:ml-4 md:text-left">
                <h1 class="text-xl font-bold md:text-2xl lg:text-3xl text-sky-800 dark:text-sky-400">
                    Escola da Magistratura do Estado do Espírito Santo
                </h1>
            </div>
        </div>

        <div
            class="w-full max-w-lg p-2 bg-white border border-gray-200 md:max-w-2xl lg:max-w-4xl md:p-6 lg:p-8 dark:bg-gray-900 dark:border-gray-700 rounded-xl">

            <div class="max-w-2xl mx-auto p-6 bg-white rounded-lg">
                @if ($curso)
                    <div class="mb-6 text-center">
                        <h2 class="text-2xl font-semibold text-sky-700 dark:text-sky-300">
                            {{ $curso->titulo }}
                        </h2>

                        @if ($disponivel == true)
                            <p class="mt-2 text-lg text-sky-600 dark:text-sky-400">
                                {{ $inicio }} - {{ $termino }}
                            </p>
                        @endif
                    </div>
                @endif

                @if ($disponivel == true)
                    <form wire:submit.prevent="submit" @keydown.enter.prevent>
                        {{ $this->form }}

                        @if ($this->formularioValidado() && $this->localizacaoObrigatoria())
                            <div class="mt-4 p-4 border rounded-lg bg-sky-50">
                                <p class="mb-3 text-sm text-gray-700">
                                    Para registrar a frequência, valide sua localização no local do evento.
                                </p>

                                <x-filament::button type="button" onclick="capturarLocalizacao()">
                                    Validar localização
                                </x-filament::button>

                                @if ($mensagemLocalizacao)
                                    <div
                                        class="mt-3 text-sm {{ $localizacaoValida ? 'text-green-700' : 'text-red-700' }}">
                                        <strong>{{ $mensagemLocalizacao }}</strong>

                                        @if ($distancia)
                                            <br>
                                            Distância aproximada: {{ $distancia }}m
                                        @endif

                                        @if ($accuracy)
                                            <br>
                                            Precisão do GPS: {{ $accuracy }}m
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endif

                        <div class="mt-4">
                            @if ($this->formularioValidado())
                                <x-filament::button type="submit" :disabled="! $this->podeRegistrarFrequencia()">
                                    Registrar frequência
                                </x-filament::button>
                            @endif
                        </div>
                    </form>
                @else
                    <div class="mt-4 p-2 bg-yellow-100 text-yellow-800 rounded-lg text-center">
                        @if ($this->mensagem)
                            <span>{{ $this->mensagem }}</span>
                        @else
                            <span>Frequência não disponível.</span>
                        @endif
                    </div>
                @endif

                @if (session('success'))
                    <div class="mt-4 p-2 bg-green-100 text-green-800 rounded">
                        {{ session('success') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function capturarLocalizacao() {
            if (!navigator.geolocation) {
                alert('Seu navegador não suporta geolocalização.');
                return;
            }

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    Livewire.dispatch('locationCaptured', {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                        accuracy: Math.round(position.coords.accuracy)
                    });
                },
                function(error) {
                    let message = 'Não foi possível capturar sua localização.';

                    if (error.code === error.PERMISSION_DENIED) {
                        message = 'Permissão de localização negada. Libere o acesso à localização no navegador.';
                    }

                    if (error.code === error.POSITION_UNAVAILABLE) {
                        message = 'Localização indisponível no momento.';
                    }

                    if (error.code === error.TIMEOUT) {
                        message = 'Tempo esgotado ao tentar capturar a localização.';
                    }

                    alert(message);
                }, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        }
    </script>
</div>
