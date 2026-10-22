@php
    $demanda_id = $getRecord()->id;
    $tarefas = \App\Models\Administrativo\Tarefas::where('demanda_id', $demanda_id)->get();
    $total = $tarefas->count();
    $concluidas = $tarefas->whereNotNull('data_concluida')->count();

    $percentual = $total > 0
        ? number_format(($concluidas * 100) / $total, 0, '.', '')
        : 0;
@endphp

<div class="flex items-center w-full h-4 overflow-hidden rounded-full shadow-inner bg-slate-100">
    <div class="justify-center h-full px-1 text-xs font-semibold text-white rounded-r-full shadow-inner bg-primary-600" style="width: {{ $percentual }}%;">
        {{ $percentual }}%
    </div>
</div>
