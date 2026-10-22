<div class="ms-4">
    <x-filament::input.wrapper>
        <x-filament::input.select wire:model.live="setorAtual" aria-label="Selecionar Setor">
            @if($setores->isEmpty())
            <option disabled>Nenhum setor atribuído</option>
            @else
            @foreach($setores as $setor)
            <option value="{{ $setor->id }}">{{ $setor->setor }}</option>
            @endforeach
            @endif
        </x-filament::input.select>
    </x-filament::input.wrapper>
</div>