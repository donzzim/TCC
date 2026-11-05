<?php

namespace App\Monitoramento\Armazenamento;

interface RepositorioDeMetricas
{
    public function instalado(): bool;

    public function gravarAgregados(array $agregados): void;

    public function gravarDetalhadas(array $registros): void;

    public function consolidarHora(int $hora): void;

    public function limpar(int $agora): void;

    public function registrarBatimento(string $tarefa, int $quando, bool $sucesso, ?string $mensagem = null): void;
}
