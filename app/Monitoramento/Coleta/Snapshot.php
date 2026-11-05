<?php

namespace App\Monitoramento\Coleta;

final readonly class Snapshot
{
    public function __construct(
        public int $instante,
        public ?array $online,
        public ?array $mysql,
        public ?array $fila,
        public ?array $servidor,
    ) {
    }

    public function toArray(): array
    {
        return [
            'instante' => $this->instante,
            'online' => $this->online,
            'mysql' => $this->mysql,
            'fila' => $this->fila,
            'servidor' => $this->servidor,
        ];
    }

    public static function fromArray(array $d): self
    {
        return new self(
            instante: (int) ($d['instante'] ?? 0),
            online: is_array($d['online'] ?? null) ? $d['online'] : null,
            mysql: is_array($d['mysql'] ?? null) ? $d['mysql'] : null,
            fila: is_array($d['fila'] ?? null) ? $d['fila'] : null,
            servidor: is_array($d['servidor'] ?? null) ? $d['servidor'] : null,
        );
    }
}
