<?php

namespace App\Monitoramento\Armazenamento;

use App\Monitoramento\Coleta\RegistroDeRequisicao;
use App\Monitoramento\Coleta\Snapshot;
use App\Monitoramento\Leitura\Periodo;
use App\Monitoramento\Leitura\ResumoDoPeriodo;
use Closure;
use Throwable;

final class RepositorioSeguro implements RepositorioDeMetricas
{
    public function __construct(private readonly RepositorioDeMetricas $repositorio)
    {
    }

    public function instalado(): bool
    {
        return $this->ler(fn () => $this->repositorio->instalado(), false);
    }

    public function gravarAgregados(array $agregados): void
    {
        $this->repositorio->gravarAgregados($agregados);
    }

    public function gravarDetalhadas(array $registros): void
    {
        $this->repositorio->gravarDetalhadas($registros);
    }

    public function processarArquivo(string $nome, array $agregados, array $detalhadas): bool
    {
        return $this->repositorio->processarArquivo($nome, $agregados, $detalhadas);
    }

    public function consolidarHora(int $hora): void
    {
        $this->repositorio->consolidarHora($hora);
    }

    public function limpar(int $agora): void
    {
        $this->repositorio->limpar($agora);
    }

    public function registrarBatimento(string $tarefa, int $quando, bool $sucesso, ?string $mensagem = null): void
    {
        $this->repositorio->registrarBatimento($tarefa, $quando, $sucesso, $mensagem);
    }

    public function gravarSnapshot(Snapshot $s): void
    {
        $this->repositorio->gravarSnapshot($s);
    }

    public function resumo(Periodo $p, ?string $contexto, int $agora): ResumoDoPeriodo
    {
        return $this->ler(
            fn () => $this->repositorio->resumo($p, $contexto, $agora),
            new ResumoDoPeriodo(0, 0, 0, null, null, null, 0.0, 0.0),
        );
    }

    public function serieLatencia(Periodo $p, ?string $contexto, int $agora): array
    {
        return $this->ler(fn () => $this->repositorio->serieLatencia($p, $contexto, $agora), []);
    }

    public function serieVolume(Periodo $p, int $agora): array
    {
        return $this->ler(fn () => $this->repositorio->serieVolume($p, $agora), []);
    }

    public function serieErros(Periodo $p, ?string $contexto, int $agora): array
    {
        return $this->ler(fn () => $this->repositorio->serieErros($p, $contexto, $agora), []);
    }

    public function rotas(Periodo $p, ?string $contexto, string $ordem, int $limite, int $agora, bool $somenteLivewire = false): array
    {
        return $this->ler(fn () => $this->repositorio->rotas($p, $contexto, $ordem, $limite, $agora, $somenteLivewire), []);
    }

    public function requisicoesLentas(Periodo $p, ?string $contexto, int $limite, int $agora): array
    {
        return $this->ler(fn () => $this->repositorio->requisicoesLentas($p, $contexto, $limite, $agora), []);
    }

    public function requisicao(string $requestId): ?RegistroDeRequisicao
    {
        return $this->ler(fn () => $this->repositorio->requisicao($requestId), null);
    }

    public function ultimoSnapshot(): ?Snapshot
    {
        return $this->ler(fn () => $this->repositorio->ultimoSnapshot(), null);
    }

    public function serieSnapshots(Periodo $p, int $agora): array
    {
        return $this->ler(fn () => $this->repositorio->serieSnapshots($p, $agora), []);
    }

    public function batimentos(): array
    {
        return $this->ler(fn () => $this->repositorio->batimentos(), []);
    }

    private function ler(Closure $leitura, mixed $padrao): mixed
    {
        try {
            return $leitura();
        } catch (Throwable) {
            return $padrao;
        }
    }
}
