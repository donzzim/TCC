<?php

namespace App\Monitoramento\Armazenamento;

use App\Monitoramento\Coleta\RegistroDeRequisicao;
use App\Monitoramento\Coleta\Snapshot;
use App\Monitoramento\Leitura\Periodo;
use App\Monitoramento\Leitura\ResumoDoPeriodo;

interface RepositorioDeMetricas
{
    public function instalado(): bool;

    public function gravarAgregados(array $agregados): void;

    public function gravarDetalhadas(array $registros): void;

    public function processarArquivo(string $nome, array $agregados, array $detalhadas): bool;

    public function consolidarHora(int $hora): void;

    public function limpar(int $agora): void;

    public function registrarBatimento(string $tarefa, int $quando, bool $sucesso, ?string $mensagem = null): void;

    public function gravarSnapshot(Snapshot $s): void;

    public function resumo(Periodo $p, ?string $contexto, int $agora): ResumoDoPeriodo;

    public function serieLatencia(Periodo $p, ?string $contexto, int $agora): array;

    public function serieVolume(Periodo $p, int $agora): array;

    public function serieErros(Periodo $p, ?string $contexto, int $agora): array;

    public function rotas(Periodo $p, ?string $contexto, string $ordem, int $limite, int $agora, bool $somenteLivewire = false): array;

    public function requisicoesLentas(Periodo $p, ?string $contexto, int $limite, int $agora): array;

    public function requisicao(string $requestId): ?RegistroDeRequisicao;

    public function ultimoSnapshot(): ?Snapshot;

    public function serieSnapshots(Periodo $p, int $agora): array;

    public function batimentos(): array;
}
