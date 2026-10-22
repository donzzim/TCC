<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Data;

use Carbon\CarbonImmutable;

/**
 * DTO de filtros dos Relatorios Gerais.
 *
 * Centraliza a normalizacao de periodo que no legado era feita com
 * `str_replace('00:00:00', '00:00:01' | '23:59:59' | '', ...)` espalhada por
 * cada arquivo. Aqui os helpers de data cobrem todos os formatos usados:
 *
 *  - inicioHora()  => "Y-m-d 00:00:01"  (legado: str_replace -> 00:00:01)
 *  - fimHora()     => "Y-m-d 23:59:59"  (legado: str_replace -> 23:59:59)
 *  - inicioData()  => "Y-m-d"           (legado: str_replace -> '')
 *  - fimData()     => "Y-m-d"
 *  - inicioMesDoTermino() => 1o dia do mes do `termino` (legado: explode('-', $termino) . "-01")
 *  - inicioAno()   => 1o dia do ano do `inicio`
 *  - mesReferencia(-n) => "Y-m" do `termino` deslocado n meses
 */
final readonly class FiltrosRelatorio
{
    /**
     * @param  list<int>  $materiais
     */
    public function __construct(
        public ?CarbonImmutable $dataInicio = null,
        public ?CarbonImmutable $dataTermino = null,
        public ?int $conta = null,
        public ?string $situacaoContabil = null,
        public ?string $centroCusto = null,
        public ?string $numeroProcesso = null,
        public ?string $notaFiscal = null,
        public ?string $grupo = null,
        public ?string $acuracia = null,
        public array $materiais = [],
    ) {}

    /**
     * Aceita as chaves do form Filament e as chaves GET do legado.
     *
     * @param  array<string, mixed>  $dados
     */
    public static function fromArray(array $dados): self
    {
        $primeiro = static fn (array $chaves) => collect($chaves)
            ->map(fn ($c) => $dados[$c] ?? null)
            ->first(fn ($v) => $v !== null && $v !== '');

        $data = static function ($valor): ?CarbonImmutable {
            if ($valor === null || $valor === '') {
                return null;
            }

            return CarbonImmutable::parse($valor);
        };

        $materiais = $primeiro(['materiais', 'material']);
        $materiais = match (true) {
            is_array($materiais) => array_values(array_map('intval', $materiais)),
            $materiais === null => [],
            default => array_values(array_map('intval', array_filter(explode(',', (string) $materiais), 'strlen'))),
        };

        $int = static fn ($v) => ($v === null || $v === '') ? null : (int) $v;
        $str = static fn ($v) => ($v === null || $v === '') ? null : (string) $v;

        return new self(
            dataInicio: $data($primeiro(['data_inicio', 'inicio', 'dataInicio'])),
            dataTermino: $data($primeiro(['data_termino', 'termino', 'dataTermino'])),
            conta: $int($primeiro(['conta_contabil', 'conta'])),
            situacaoContabil: $str($primeiro(['situacao_contabil', 'situacao'])),
            centroCusto: $str($primeiro(['centro_custo', 'centrocusto'])),
            numeroProcesso: $str($primeiro(['numero_processo', 'processo'])),
            notaFiscal: $str($primeiro(['nota_fiscal', 'notafiscal'])),
            grupo: $str($primeiro(['grupo'])),
            acuracia: $str($primeiro(['acuracia'])),
            materiais: $materiais,
        );
    }

    public function inicioObrigatorio(): CarbonImmutable
    {
        return $this->dataInicio ?? throw new \InvalidArgumentException('Filtro "data_inicio" obrigatorio.');
    }

    public function terminoObrigatorio(): CarbonImmutable
    {
        return $this->dataTermino ?? throw new \InvalidArgumentException('Filtro "data_termino" obrigatorio.');
    }

    /** "Y-m-d 00:00:01" (legado: str_replace 00:00:00 -> 00:00:01). */
    public function inicioHora(): string
    {
        return $this->inicioObrigatorio()->format('Y-m-d').' 00:00:01';
    }

    /** "Y-m-d 23:59:59" (legado: str_replace 00:00:00 -> 23:59:59). */
    public function fimHora(): string
    {
        return $this->terminoObrigatorio()->format('Y-m-d').' 23:59:59';
    }

    public function inicioData(): string
    {
        return $this->inicioObrigatorio()->format('Y-m-d');
    }

    public function fimData(): string
    {
        return $this->terminoObrigatorio()->format('Y-m-d');
    }

    /** 1o dia do mes do `termino` — legado: explode('-', $termino) => "$ano-$mes-01". */
    public function inicioMesDoTermino(): string
    {
        return $this->terminoObrigatorio()->startOfMonth()->format('Y-m-d');
    }

    /** 1o dia do ano do `inicio` — legado: "$ano-01-01". */
    public function inicioAno(): string
    {
        return $this->inicioObrigatorio()->startOfYear()->format('Y-m-d');
    }

    /** "Y-m" do `termino` deslocado `$offsetMeses` meses (0 = mes do termino). */
    public function mesReferencia(int $offsetMeses = 0): string
    {
        return $this->terminoObrigatorio()->addMonths($offsetMeses)->format('Y-m');
    }

    /** "Ym" do `termino` deslocado `$offsetMeses` meses (legado: DATE_FORMAT(..., '%Y%m')). */
    public function anoMesReferencia(int $offsetMeses = 0): string
    {
        return $this->terminoObrigatorio()->addMonths($offsetMeses)->format('Ym');
    }

    public function anoDoInicio(): int
    {
        return (int) $this->inicioObrigatorio()->year;
    }

    public function anoDoTermino(): int
    {
        return (int) $this->terminoObrigatorio()->year;
    }

    public function temMateriais(): bool
    {
        return $this->materiais !== [];
    }
}
