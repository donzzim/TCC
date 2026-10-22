@php
    // Legado: mascara do subelemento "#.#.##.##.##" sobre CodigodaClasse.
    $mascara = static function (?string $codigo): string {
        if (! $codigo) {
            return '—';
        }
        $s = preg_replace('/\D/', '', $codigo);
        $m = '#.#.##.##.##';
        for ($i = 0, $p = 0; $i < strlen($s) && ($p = strpos($m, '#', $p)) !== false; $i++, $p++) {
            $m[$p] = $s[$i];
        }
        return $m;
    };
@endphp

<x-relatorios::pagina :titulo="$titulo" secao="Seção de Material de Consumo"
    :filtros="$filtros" :data-emissao="$data_emissao">
    <x-relatorios::tabela
        :linhas="$dados"
        agruparPor="codigo_classe"
        :somar="['sub_total']"
        :total="true"
        :grupoRotulo="fn ($v) => 'Subelemento: ' . $mascara($v)"
        :colunas="[
            'descricao_detalhada' => ['label' => 'Material', 'width' => '55%'],
            'quantidade_consumida' => ['label' => 'Qtde Consumida', 'align' => 'right', 'fmt' => 'inteiro'],
            'preco_atual' => ['label' => 'Último Preço', 'align' => 'right', 'fmt' => 'moeda'],
            'sub_total' => ['label' => 'SubTotal', 'align' => 'right', 'fmt' => 'moeda'],
        ]" />
</x-relatorios::pagina>
