@php
    $assinaturaEletronica = $assinaturaEletronica ?? ((int) ($arquivoDigital->situacao ?? 0) === 1);
    $usuarioDestinatario = $usuarioDestinatario ?? $usuarioEmitente ?? null;
    $cargoDestinatario = $cargoDestinatario ?? $cargoEmitente ?? null;
    $cpfDestinatario = $cpfDestinatario ?? $cpfEmitente ?? null;
    $dataEmissao = $dataEmissao ?? date('d/m/Y', strtotime($termo->date_time ?? now()));
    $dataAssinatura = $dataAssinatura ?? $dataEmissao;
    $numeroTermo = "{$termo->num_termo}/{$termo->ano_termo}";
@endphp

<x-impressao::documento
    titulo="Termo de Responsabilidade"
    :titulo-aba="'Termo de Responsabilidade ' . $numeroTermo"
    secao="Seção de Patrimônio"
    kicker="Bens patrimoniais"
    :identificador="'Nº ' . $numeroTermo"
    :data-emissao="$dataEmissao"
    rodape="e-GAPes · Seção de Patrimônio"
    :rodape-direita="'Termo de Responsabilidade Nº ' . $numeroTermo"
    :brasao-embutido="true"
>
    <x-slot:antes>
        @unless ($assinaturaEletronica)
            <div class="doc-aviso">
                <strong>Importante!</strong>
                <p>Para concluir a transferência, solicite <b>assinatura eletrônica</b> ao servidor recebedor dos bens (setor destinatário), caso contrário, eles permanecerão na relação de bens do setor remetente e a transferência ficará pendente no sistema.</p>
                <p>Se a retirada ocorrer pela equipe de logística, imprima este Termo e solicite o preenchimento dos campos no final do documento (identificação, assinatura e data de embarque). Em seguida, digitalize e envie <b>o PDF, por meio do botão "anexar termo" constante no menu "Patrimônio>Movimentação de Materiais"</b>, objetivando comprovar a retirada no setor.</p>
            </div>
        @endunless
    </x-slot:antes>

    <p class="doc-declaracao">
        Declaro pelo presente documento de responsabilidade que os materiais especificados abaixo estão devidamente localizados nesta unidade. Comprometo-me a zelar pelo bom uso e guarda dos mesmos, bem como informar a Seção de Patrimônio eventual modificação de sua localização e/ou qualquer defeito/avaria, sob pena de responsabilização.
    </p>

    <dl class="doc-campos" style="--colunas: 3;">
        <div class="doc-campo">
            <dt>Unidade</dt>
            <dd>{{ mb_strtoupper($unidade ?? 'TRIBUNAL DE JUSTIÇA DO ESPÍRITO SANTO') }}</dd>
        </div>
        <div class="doc-campo">
            <dt>Setor</dt>
            <dd>{{ mb_strtoupper($setor ?? 'NÃO INFORMADO') }}</dd>
        </div>
        <div class="doc-campo">
            <dt>Complemento</dt>
            <dd>{{ mb_strtoupper($complemento ?? 'NÃO INFORMADO') }}</dd>
        </div>
    </dl>

    <div class="doc-table-wrap">
        <table class="rel confortavel">
            <thead>
            <tr>
                <th class="a-center" scope="col" style="width: 42px;">Item</th>
                <th class="a-center" scope="col" style="width: 74px;">Patrimônio</th>
                <th class="a-left" scope="col">Descrição do bem / Marca / Modelo</th>
                <th class="a-center" scope="col" style="width: 90px;">Série</th>
                <th class="a-center" scope="col" style="width: 70px;">Situação</th>
                <th class="a-center" scope="col" style="width: 54px;">Andar</th>
                <th class="a-right" scope="col" style="width: 78px;">Valor</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($bens as $index => $bem)
                @php
                    $valor = $bem->ValorCalculado ?? 0;
                    if (! isset($bem->ValorCalculado)) {
                        $dataInc = $bem->DatadeIncorporacao ?? date('Y-m-d');
                        $valor = (strtotime($dataInc) < strtotime('2015-01-01')) ? ($bem->ValordaReavaliacao ?? 0) : ($bem->ValorAquisicao ?? 0);
                    }
                    $marca = $bem->marca_desc ?? ($bem->marca_re->descricao ?? null);
                    $modelo = $bem->modelo_desc ?? ($bem->modelo_re->descricao ?? null);
                @endphp
                <tr>
                    <td class="a-center">{{ $index + 1 }}</td>
                    <td class="a-center forte">{{ $bem->NumPatrimonio }}</td>
                    <td>
                        {{ collect([$bem->Descricao, $marca ? "Marca: {$marca}" : null, $modelo ? "Modelo: {$modelo}" : null])->filter()->implode(", ") }}
                    </td>
                    <td class="a-center">{{ $bem->NumerodeSerie ?: '-' }}</td>
                    <td class="a-center">{{ mb_strtoupper($bem->EstadodeConservacao ?? 'BOM') }}</td>
                    <td class="a-center">{{ $bem->andar_atual ?: '-' }}</td>
                    <td class="a-right nowrap">{{ number_format((float) $valor, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="doc-assinaturas">
        <div class="doc-assinatura">
            @if ($assinaturaEletronica)
                <div class="doc-assinatura-linha">
                    <strong>Emitente (Gerado por)</strong>
                    <span>{{ mb_strtoupper($usuarioEmitente ?? 'USUÁRIO') }}</span>
                    <span>{{ mb_strtoupper($cargoEmitente ?? 'SERVIDOR') }}</span>
                    <span>{{ $dataEmissao }}</span>
                </div>
            @else
                <div class="doc-assinatura-caixa"></div>
                <div class="doc-assinatura-linha">
                    <strong>Setor (Emitente)</strong>
                    <span>{{ mb_strtoupper($usuarioEmitente ?? 'USUÁRIO') }} ({{ mb_strtoupper($cargoEmitente ?? 'SERVIDOR') }})</span>
                    <span>Carimbo e Assinatura</span>
                </div>
            @endif
        </div>
        <div class="doc-assinatura">
            @if ($assinaturaEletronica)
                <div class="doc-assinatura-linha">
                    <strong>Destinatário (Recebido por)</strong>
                    <span>{{ mb_strtoupper($usuarioDestinatario ?? 'USUÁRIO') }}</span>
                    <span>{{ mb_strtoupper($cargoDestinatario ?? 'SERVIDOR') }}</span>
                    <span>{{ $dataAssinatura }}</span>
                </div>
            @else
                <div class="doc-assinatura-caixa"></div>
                <div class="doc-assinatura-linha">
                    <strong>Setor (Destinatário)</strong>
                    <span>Carimbo e Assinatura</span>
                    <span class="doc-assinatura-data">Recebido em ____/____/______.</span>
                </div>
            @endif
        </div>
    </div>

    <p class="doc-nota" style="margin-top: 30px;">
        @if ($assinaturaEletronica)
            Documento assinado eletronicamente por <b>{{ mb_strtoupper($usuarioDestinatario ?? 'USUÁRIO') }}</b>, CPF: <b>{{ $cpfDestinatario ?: 'NÃO INFORMADO' }}</b>, {{ mb_strtoupper($cargoDestinatario ?? 'SERVIDOR') }}, em {{ $dataAssinatura }}, conforme art. 1º do Ato Normativo TJES Nº 75/2018. Código de Validação {{ $numeroTermo }}.
        @else
            Documento gerado em {{ date('d/m/Y', strtotime($termo->date_time ?? now())) }}.<br>
            Os itens constantes neste termo foram devidamente conferidos e embarcados, sendo sua guarda de responsabilidade do transportador até a efetivação da entrega.
        @endif
    </p>
</x-impressao::documento>
