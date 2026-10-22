@php
    /** @var \App\Models\egap\Agendamento\Solicitacao $solicitacao */
    /** @var \Illuminate\Support\Collection<int, \App\Models\egap\Agendamento\Recursos> $recursos */
    $justificativa = is_array($solicitacao->justificativa) ? $solicitacao->justificativa : [];
@endphp

<div class="space-y-4">
    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
        <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
            <div>
                <dt class="font-semibold text-gray-900 dark:text-white">Solicitante</dt>
                <dd class="text-gray-600 dark:text-gray-300">{{ $solicitacao->idSolicitanteRef?->name ?? '-' }}</dd>
            </div>
            <div>
                <dt class="font-semibold text-gray-900 dark:text-white">Setor / Unidade</dt>
                <dd class="text-gray-600 dark:text-gray-300">
                    {{ $solicitacao->setorSolicitanteRef?->setor ?? '-' }}
                    @if ($solicitacao->unidadeSolicitanteRef?->unidadeorganizacional)
                        ({{ $solicitacao->unidadeSolicitanteRef->unidadeorganizacional }})
                    @endif
                </dd>
            </div>
            <div>
                <dt class="font-semibold text-gray-900 dark:text-white">Região</dt>
                <dd class="text-gray-600 dark:text-gray-300">{{ $solicitacao->regiaoRef?->regiao ?? '-' }}</dd>
            </div>
            <div>
                <dt class="font-semibold text-gray-900 dark:text-white">Contatos</dt>
                <dd class="text-gray-600 dark:text-gray-300">
                    {{ collect([$justificativa['telefone'] ?? null, $justificativa['celular'] ?? null])->filter()->implode(' / ') ?: '-' }}
                </dd>
            </div>
            <div>
                <dt class="font-semibold text-gray-900 dark:text-white">Saída</dt>
                <dd class="text-gray-600 dark:text-gray-300">
                    {{ $solicitacao->local_saida ?: '-' }}
                    @if ($solicitacao->data_inicio)
                        <br>{{ \Illuminate\Support\Carbon::parse($solicitacao->data_inicio)->format('d/m/Y') }}
                        @if ($solicitacao->hora_inicio)
                            {{ \Illuminate\Support\Carbon::parse($solicitacao->hora_inicio)->format('H:i') }}
                        @endif
                    @endif
                </dd>
            </div>
            <div>
                <dt class="font-semibold text-gray-900 dark:text-white">Destino</dt>
                <dd class="text-gray-600 dark:text-gray-300">
                    @php
                        $destino = $solicitacao->local_destino ?: collect($justificativa['destino'] ?? [])->implode(', ');
                    @endphp
                    {{ $destino ?: '-' }}
                    @if ($solicitacao->data_termino)
                        <br>{{ \Illuminate\Support\Carbon::parse($solicitacao->data_termino)->format('d/m/Y') }}
                        @if ($solicitacao->hora_termino)
                            {{ \Illuminate\Support\Carbon::parse($solicitacao->hora_termino)->format('H:i') }}
                        @endif
                    @endif
                </dd>
            </div>
            <div>
                <dt class="font-semibold text-gray-900 dark:text-white">Passageiros</dt>
                <dd class="text-gray-600 dark:text-gray-300">{{ collect($justificativa['passageiros'] ?? [])->implode(', ') ?: '-' }}</dd>
            </div>
            <div>
                <dt class="font-semibold text-gray-900 dark:text-white">Veículo com motorista?</dt>
                <dd class="text-gray-600 dark:text-gray-300">
                    {{ $justificativa['veiculo_motorista'] ?? '-' }}
                    @if (($justificativa['veiculo_motorista'] ?? null) === 'Sim')
                        • Aguarda no destino: {{ $justificativa['aguardar_destino'] ?? 'Nao' }}
                    @endif
                </dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="font-semibold text-gray-900 dark:text-white">Justificativa</dt>
                <dd class="text-gray-600 dark:text-gray-300">{{ $justificativa['justificativa'] ?? '-' }}</dd>
            </div>
            @if (filled($justificativa['justificativa_intempestividade'] ?? null))
                <div class="sm:col-span-2">
                    <dt class="font-semibold text-gray-900 dark:text-white">Justificativa de intempestividade</dt>
                    <dd class="text-gray-600 dark:text-gray-300">{{ $justificativa['justificativa_intempestividade'] }}</dd>
                </div>
            @endif
            @if (filled($solicitacao->motivo_cancelamento))
                <div class="sm:col-span-2">
                    <dt class="font-semibold text-gray-900 dark:text-white">
                        {{ (int) $solicitacao->id_situacao === \App\Models\egap\Agendamento\Solicitacao::SITUACAO_INVALIDADO ? 'Motivo da invalidação' : 'Motivo do cancelamento' }}
                    </dt>
                    <dd class="text-gray-600 dark:text-gray-300">{{ $solicitacao->motivo_cancelamento }}</dd>
                </div>
            @endif
            @if (filled($justificativa['num_processo_sei'] ?? null))
                <div>
                    <dt class="font-semibold text-gray-900 dark:text-white">Processo SEI</dt>
                    <dd class="text-gray-600 dark:text-gray-300">{{ $justificativa['num_processo_sei'] }}</dd>
                </div>
            @endif
            @php
                $anexos = collect([
                    'Documentos e declaração' => $justificativa['doc_declaracao'] ?? null,
                    'Anexo da justificativa' => $justificativa['doc_just'] ?? null,
                ])->filter();
            @endphp
            @if ($anexos->isNotEmpty())
                <div class="sm:col-span-2">
                    <dt class="font-semibold text-gray-900 dark:text-white">Anexos</dt>
                    <dd class="space-x-3">
                        @foreach ($anexos as $rotulo => $caminho)
                            <a
                                href="{{ \App\Filament\Egap\Support\LegacyFileUrl::resolve($caminho, config('app.egap')) }}"
                                target="_blank"
                                class="text-primary-600 hover:underline dark:text-primary-400"
                            >{{ $rotulo }}</a>
                        @endforeach
                    </dd>
                </div>
            @endif
        </dl>
    </div>

    <div>
        <h4 class="mb-2 text-sm font-semibold text-gray-900 dark:text-white">Motorista e veículo agendados</h4>
        @forelse ($recursos as $recurso)
            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <dl class="grid grid-cols-1 gap-2 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="font-semibold text-gray-900 dark:text-white">Condutor</dt>
                        <dd class="text-gray-600 dark:text-gray-300">{{ $recurso->condutorRef?->idPessoaRef?->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-gray-900 dark:text-white">Contato do condutor</dt>
                        <dd class="text-gray-600 dark:text-gray-300">{{ $recurso->condutorRef?->contato ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-gray-900 dark:text-white">Veículo</dt>
                        <dd class="text-gray-600 dark:text-gray-300">
                            {{ $recurso->veiculoRef?->descricao ?? '-' }}
                            @if ($recurso->veiculoRef?->placa)
                                / {{ $recurso->veiculoRef->placa }}
                            @endif
                        </dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="font-semibold text-gray-900 dark:text-white">Observação</dt>
                        <dd class="text-gray-600 dark:text-gray-300">{{ $recurso->observacao ?: '-' }}</dd>
                    </div>
                </dl>
            </div>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum motorista/veículo agendado ainda.</p>
        @endforelse
    </div>
</div>
