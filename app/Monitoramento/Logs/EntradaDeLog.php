<?php

namespace App\Monitoramento\Logs;

final readonly class EntradaDeLog
{
    public function __construct(
        public string $data,
        public string $ambiente,
        public string $nivel,
        public string $mensagem,
        public ?array $contexto,
        public string $trace,
        public string $arquivo,
    ) {
    }
}
