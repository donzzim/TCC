<?php

namespace App\Monitoramento\Coleta;

use DateTimeImmutable;
use DateTimeZone;
use Generator;
use Throwable;

class BufferDeRequisicoes
{
    public function __construct(private readonly string $diretorio)
    {
    }

    public function diretorio(): string
    {
        return $this->diretorio;
    }

    public function anexar(RegistroDeRequisicao $r): void
    {
        try {
            if (! is_dir($this->diretorio)) {
                @mkdir($this->diretorio, 0775, true);
            }

            $arquivo = $this->diretorio.'/'.gmdate('YmdHi', (int) $r->instante).'.ndjson';
            $linha = json_encode($r->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

            if ($linha !== false) {
                @file_put_contents($arquivo, $linha."\n", FILE_APPEND | LOCK_EX);
            }
        } catch (Throwable) {
        }
    }

    public function reservarProntos(int $agora): array
    {
        $minutoAtual = intdiv($agora, 60) * 60;
        $reservados = [];

        foreach (glob($this->diretorio.'/*.ndjson') ?: [] as $arquivo) {
            $minuto = $this->minutoDoArquivo($arquivo);

            if ($minuto === null || $minuto >= $minutoAtual) {
                continue;
            }

            $destino = substr($arquivo, 0, -strlen('.ndjson')).'-'.bin2hex(random_bytes(4)).'.processando';

            if (@rename($arquivo, $destino)) {
                $reservados[] = $destino;
            }
        }

        sort($reservados);

        return $reservados;
    }

    public function pendentesAntigos(int $agora, int $idadeSeg): array
    {
        $antigos = [];

        foreach (glob($this->diretorio.'/*.processando') ?: [] as $arquivo) {
            $modificado = @filemtime($arquivo);

            if ($modificado !== false && $agora - $modificado > $idadeSeg) {
                $antigos[] = $arquivo;
            }
        }

        sort($antigos);

        return $antigos;
    }

    public function ler(string $arquivo): Generator
    {
        $handle = @fopen($arquivo, 'rb');

        if ($handle === false) {
            return;
        }

        try {
            while (($linha = fgets($handle)) !== false) {
                $linha = trim($linha);

                if ($linha === '') {
                    continue;
                }

                $dados = json_decode($linha, true);

                if (! is_array($dados)) {
                    continue;
                }

                try {
                    yield RegistroDeRequisicao::fromArray($dados);
                } catch (Throwable) {
                    continue;
                }
            }
        } finally {
            fclose($handle);
        }
    }

    public function descartar(string $arquivo): void
    {
        @unlink($arquivo);
    }

    private function minutoDoArquivo(string $arquivo): ?int
    {
        $nome = basename($arquivo, '.ndjson');

        if (! preg_match('/^\d{12}$/', $nome)) {
            return null;
        }

        $data = DateTimeImmutable::createFromFormat('!YmdHi', $nome, new DateTimeZone('UTC'));

        return $data === false ? null : $data->getTimestamp();
    }
}
