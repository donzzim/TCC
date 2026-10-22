<div class="p-0 bg-white text-black font-sans text-[10px] leading-tight print:p-0" style="font-family: Arial, sans-serif; font-size: 10px;">
    <div class="border-2 border-black">

        {{-- ===== CABEÇALHO ===== --}}
        <div class="flex items-stretch border-b-2 border-black">

            {{-- Brasão --}}
            <div class="flex items-center justify-center border-r-2 border-black px-3 py-2" style="min-width: 80px;">
                <img src="{{ asset('images/brasao-tjes.png') }}" alt="Brasão TJES" style="width:56px; height:56px; object-fit:contain;">
            </div>

            {{-- Título central --}}
            <div class="flex flex-col justify-center px-3 py-2 flex-1">
                <p class="font-bold uppercase text-[13px]">TRIBUNAL DE JUSTIÇA DO ESTADO ES</p>
                <p class="text-[10px] mt-0.5">Cálculo de Depreciação Mensal - Bens Patrimoniais</p>
            </div>

            {{-- Data e Setor --}}
            <div class="flex flex-col justify-between items-end border-l-2 border-black px-3 py-2 text-right" style="min-width: 130px;">
                <p class="text-[10px]">{{ now()->format('d/m/Y') }}</p>
                <p class="font-bold uppercase text-[10px] mt-4">Setor de Patrimônio</p>
            </div>
        </div>

        {{-- ===== FAIXA TÍTULO ===== --}}
        <div class="bg-gray-100 border-b-2 border-black py-1 text-center">
            <p class="font-bold uppercase tracking-widest text-[11px]">Cálculo de Depreciação Mensal</p>
        </div>

        {{-- ===== BLOCO DE INFORMAÇÕES ===== --}}
        <div class="border-b-2 border-black">
            <table class="w-full" style="border-collapse: collapse;">
                <tbody>
                    {{-- Linha 1 --}}
                    <tr>
                        <td class="border-r-2 border-black px-2 py-1 font-bold uppercase align-top" style="width:130px; white-space:nowrap;">
                            Patrim.
                        </td>
                        <td class="border-r-2 border-black px-2 py-1 align-top" style="width:120px;">
                            {{ $record->NumPatrimonio }}
                        </td>
                        <td class="border-r-2 border-black px-2 py-1 font-bold uppercase align-top" style="width:160px; white-space:nowrap;">
                            Descrição Detalhada
                        </td>
                        <td class="px-2 py-1 align-top">
                            {{ $record->Descricao }}
                        </td>
                    </tr>
                    {{-- Linha 2 --}}
                    <tr class="border-t border-black">
                        <td class="border-r-2 border-black px-2 py-1 font-bold uppercase align-top">
                            Conta Contábil
                        </td>
                        <td class="border-r-2 border-black px-2 py-1 align-top">
                            {{ $record->contaContabilRef->titulo ?? '1.2.3.1.1.01.42' }}
                        </td>
                        <td class="border-r-2 border-black px-2 py-1 font-bold uppercase align-top">
                            Elemento de Despesa
                        </td>
                        <td class="px-2 py-1 align-top">
                            {{ $record->elementoDespesaRef->DescricaodaClasse ?? 'Mobiliário em Geral' }}
                        </td>
                    </tr>
                    {{-- Linha 3 --}}
                    <tr class="border-t border-black">
                        <td class="border-r-2 border-black px-2 py-1 font-bold uppercase align-top">
                            Data Aquisição
                        </td>
                        <td class="border-r-2 border-black px-2 py-1 align-top">
                            {{ \Carbon\Carbon::parse($record->DataCadastro)->format('d/m/Y') }}
                        </td>
                        <td class="border-r-2 border-black px-2 py-1 font-bold uppercase align-top">
                            Data de Liberação para Uso
                        </td>
                        <td class="px-2 py-1 align-top">
                            {{ \Carbon\Carbon::parse($record->DataDisponibilizacao ?? $record->DataCadastro)->format('d/m/Y') }}
                        </td>
                    </tr>
                    {{-- Linha 4 --}}
                    <tr class="border-t border-black">
                        <td class="border-r-2 border-black px-2 py-1 font-bold uppercase align-top">
                            Valor de Aquisição
                        </td>
                        <td class="border-r-2 border-black px-2 py-1 align-top">
                            {{ number_format($record->ValorAquisicao, 2, ',', '.') }}
                        </td>
                        <td class="border-r-2 border-black px-2 py-1 font-bold uppercase align-top" colspan="1">
                            Vida Útil (Meses)
                        </td>
                        <td class="px-2 py-1 align-top">
                            {{ $vidaUtil }}
                            &nbsp;&nbsp;
                            <span class="font-bold uppercase">Valor Residual(%)</span>
                            &nbsp;&nbsp;
                            {{ number_format(($valorResidual / ($record->ValorAquisicao ?: 1)) * 100, 2, ',', '.') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- ===== TABELA DE DEPRECIAÇÃO ===== --}}
        <table class="w-full" style="border-collapse: collapse; font-size: 9px;">
            <thead>
                <tr class="bg-gray-100 border-b-2 border-black">
                    <th class="border-r border-black px-2 py-1 text-center font-bold uppercase">Item</th>
                    <th class="border-r border-black px-2 py-1 text-center font-bold uppercase">Data Cálculo</th>
                    <th class="border-r border-black px-2 py-1 text-center font-bold uppercase">Valor (R$)</th>
                    <th class="border-r border-black px-2 py-1 text-center font-bold uppercase">Vida Útil<br>(Meses)</th>
                    <th class="border-r border-black px-2 py-1 text-center font-bold uppercase">Valor<br>Residual</th>
                    <th class="border-r border-black px-2 py-1 text-center font-bold uppercase">Depreciação<br>Mensal</th>
                    <th class="border-r border-black px-2 py-1 text-center font-bold uppercase">Depreciação<br>Acumulada</th>
                    <th class="px-2 py-1 text-center font-bold uppercase">Valor Líquido<br>Contábil</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dados as $index => $linha)
                <tr class="border-b border-black" style="{{ $loop->last ? 'border-bottom: none;' : '' }}">
                    <td class="border-r border-black px-2 py-1 text-center">{{ count($dados) - $index }}</td>
                    <td class="border-r border-black px-2 py-1 text-center">{{ $linha['data'] }}</td>
                    <td class="border-r border-black px-2 py-1 text-right">{{ number_format($record->ValorAquisicao, 2, ',', '.') }}</td>
                    <td class="border-r border-black px-2 py-1 text-center">{{ $vidaUtil }}</td>
                    <td class="border-r border-black px-2 py-1 text-right">{{ number_format($valorResidual, 2, ',', '.') }}</td>
                    <td class="border-r border-black px-2 py-1 text-right">{{ number_format($linha['mensal'], 4, ',', '.') }}</td>
                    <td class="border-r border-black px-2 py-1 text-right">{{ number_format($linha['acumulada'], 4, ',', '.') }}</td>
                    <td class="px-2 py-1 text-right font-bold">{{ number_format($linha['liquido'], 4, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

    </div>{{-- fim border-2 --}}

    {{-- ===== RODAPÉ ===== --}}
    <div class="flex justify-between text-[8px] text-gray-600 italic mt-1 px-1">
        <p>Documento gerado eletronicamente em {{ now()->format('d/m/Y H:i:s') }}</p>
        <p>Página 1/1</p>
    </div>
</div>