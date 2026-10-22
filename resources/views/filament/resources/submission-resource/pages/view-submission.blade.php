<x-filament::page>
    <div class="space-y-6">
        <div class="p-6 bg-white shadow rounded-xl">
            <h2 class="mb-4 text-xl font-semibold">Resumo</h2>
            <div class="flex">
                <table class="w-full text-xs font-normal table-auto">
                    <thead>
                        <tr>
                            <td>Perguntas</td>
                            <td>Ótimo</td>
                            <td>Bom</td>
                            <td>Ruim</td>
                            <td>Péssimo</td>
                            <td>Totalmente</td>
                            <td>Parcialmente</td>
                            <td>Sem Resposta</td>
                            <td>Total</td>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($this::getResultados() as $row)
                        <tr class="text-sm font-medium">
                            <td class="text-left">{{ $row->Perguntas }}</td>
                            <th class="text-center">{{ $row->Otimo }}</th>
                            <th class="text-center">{{ $row->Bom }}</th>
                            <th class="text-center">{{ $row->Ruim }}</th>
                            <th class="text-center">{{ $row->Pessimo }}</th>
                            <th class="text-center">{{ $row->Totalmente }}</th>
                            <th class="text-center">{{ $row->Parcialmente }}</th>
                            <th class="text-center">{{ $row->SemResposta }}</th>
                            <th class="text-center">{{ $row->Total }}</th>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="p-6 bg-white shadow rounded-xl">
            <ul class="space-y-4">
                @foreach ($record->values as $value)
                    @if (!is_null($value->field->label))
                        <li class="pb-1 border-b">
                            <strong>{{ $value->field->label }}:</strong>
                            <span class="mt-1 text-gray-700">{{ $value->value }}</span>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
    </div>
</x-filament::page>
