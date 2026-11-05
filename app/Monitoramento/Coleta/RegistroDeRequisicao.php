<?php

namespace App\Monitoramento\Coleta;

use InvalidArgumentException;

final readonly class RegistroDeRequisicao
{
    private const OBRIGATORIAS = [
        'instante', 'contexto', 'rota', 'metodo', 'status', 'duracaoMs', 'sqlMs',
        'sqlQtd', 'memoriaMb', 'livewire', 'nMaisUm', 'requestId',
    ];

    public function __construct(
        public float $instante,
        public string $contexto,
        public string $rota,
        public string $metodo,
        public int $status,
        public float $duracaoMs,
        public float $sqlMs,
        public int $sqlQtd,
        public float $memoriaMb,
        public array $livewire,
        public bool $nMaisUm,
        public string $requestId,
        public ?array $detalhe = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'instante' => $this->instante,
            'contexto' => $this->contexto,
            'rota' => $this->rota,
            'metodo' => $this->metodo,
            'status' => $this->status,
            'duracaoMs' => $this->duracaoMs,
            'sqlMs' => $this->sqlMs,
            'sqlQtd' => $this->sqlQtd,
            'memoriaMb' => $this->memoriaMb,
            'livewire' => $this->livewire,
            'nMaisUm' => $this->nMaisUm,
            'requestId' => $this->requestId,
            'detalhe' => $this->detalhe,
        ];
    }

    public static function fromArray(array $d): self
    {
        foreach (self::OBRIGATORIAS as $chave) {
            if (! array_key_exists($chave, $d)) {
                throw new InvalidArgumentException("Campo obrigatório ausente: {$chave}");
            }
        }

        return new self(
            instante: (float) $d['instante'],
            contexto: (string) $d['contexto'],
            rota: (string) $d['rota'],
            metodo: (string) $d['metodo'],
            status: (int) $d['status'],
            duracaoMs: (float) $d['duracaoMs'],
            sqlMs: (float) $d['sqlMs'],
            sqlQtd: (int) $d['sqlQtd'],
            memoriaMb: (float) $d['memoriaMb'],
            livewire: (array) $d['livewire'],
            nMaisUm: (bool) $d['nMaisUm'],
            requestId: (string) $d['requestId'],
            detalhe: isset($d['detalhe']) && is_array($d['detalhe']) ? $d['detalhe'] : null,
        );
    }
}
