<x-layouts.app>
    <div class="flex flex-col items-center justify-center p-4 my-2 mx-2 h-full">
        @if($curso)
        <div class="m-auto text-center">
            <div class="p-4 m-2">
                <h1 class="text-2xl">{{ $curso->titulo }}</h1>

                @foreach ($curso->instrutores as $instrutor)
                <h1 class="text-xl">
                    <span>{{ $instrutor->name }}</span>
                </h1>
                @endforeach
                @foreach ($curso->atividades as $atividade)
                <h1 class="text-xl">
                    <span>{{ $atividade->inicio->format('d/m/Y H:i') }}</span>
                    @if ($atividade->termino)
                    <span>a {{ $atividade->termino->format('d/m/Y H:i') }}</span>
                    @endif
                </h1>
                @endforeach
            </div>
            <div class="p-4 m-4">
                <h1 class="my-12 text-xl">REGISTRO DE FREQUÊNCIA</h1>
                <img src="{{ $qrcode }}" alt="QR Code" class="w-80 h-80 m-auto" />
            </div>
            <div class="">
                <p class="text-xl">
                    <b>Escaneie o código acima</b><br />
                    com a câmera do seu celular<br />
                    <b>e preencha o campo designado</b><br />
                    para confirmar a sua participação
                </p>
                <p class="mt-4 text-xl italic">
                    Preenchimento válido somente<br />
                    durante a realizaão das atividades
                </p>
            </div>
        </div>
        @else
        <h1 class="text-xl text-red-800">Frequência indisponível</h1>
        @endif
    </div>
</x-layouts.app>
