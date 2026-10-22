@foreach ($cursos as $curso)
    <div class="curso">
        <h3>{{ $curso->titulo }}</h3>
        <p>{!! $curso->descricao !!}</p>

        @if ($curso->carga_horaria)
            <p><strong>Carga horária:</strong> {{ $curso->carga_horaria }}</p>
        @endif

        @if ($curso->atividades->isNotEmpty())
            <p><strong>Atividades:</strong></p>
            @foreach ($curso->atividades as $atividade)
                <p class="atividade">
                    {{ $atividade->titulo }}: {{ $atividade->inicio }}
                    @if ($atividade->termino)
                        a {{ $atividade->termino }}
                    @endif
                </p>
            @endforeach
        @endif
    </div>
@endforeach
