<?php

namespace App\Monitoramento\Logs;

final class LeitorDeLog
{
    private const CABECALHO = '/^\[(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2})[^\]]*\] ([\w-]+)\.(\w+): (.*)$/s';

    private const LIMITE_TRACE = 20000;

    private readonly string $diretorio;

    public function __construct(?string $diretorio = null)
    {
        $this->diretorio = rtrim($diretorio ?? (string) config('monitoramento.logs_dir', storage_path('logs')), '/\\');
    }

    public function arquivos(): array
    {
        $arquivos = glob($this->diretorio.'/*.log') ?: [];
        usort($arquivos, fn (string $a, string $b): int => (@filemtime($b) ?: 0) <=> (@filemtime($a) ?: 0));

        return $arquivos;
    }

    public function permitido(string $arquivo): bool
    {
        return in_array($arquivo, $this->arquivos(), true);
    }

    public function ultimas(string $arquivo, int $maxBytes = 2_097_152, ?string $nivel = null, ?string $texto = null, int $limite = 200): array
    {
        $handle = @fopen($arquivo, 'rb');

        if ($handle === false) {
            return [];
        }

        $tamanho = (int) (fstat($handle)['size'] ?? 0);
        $inicio = max(0, $tamanho - $maxBytes);
        fseek($handle, $inicio);

        if ($inicio > 0) {
            fgets($handle);
        }

        $entradas = [];

        foreach ($this->entradas($handle, $arquivo) as $entrada) {
            if ($nivel !== null && strcasecmp($entrada->nivel, $nivel) !== 0) {
                continue;
            }

            if ($texto !== null && $texto !== '' && stripos($entrada->mensagem."\n".$entrada->trace, $texto) === false) {
                continue;
            }

            $entradas[] = $entrada;
        }

        fclose($handle);

        return array_slice(array_reverse($entradas), 0, $limite);
    }

    public function buscarOcorrencia(string $codigo, int $maxBytesPorArquivo = 52_428_800): ?EntradaDeLog
    {
        $codigo = strtoupper(trim($codigo));

        if (! preg_match('/^[A-F0-9]{8}$/', $codigo)) {
            return null;
        }

        $agulha = '"ocorrencia":"'.$codigo.'"';

        foreach ($this->arquivos() as $arquivo) {
            $handle = @fopen($arquivo, 'rb');

            if ($handle === false) {
                continue;
            }

            $tamanho = (int) (fstat($handle)['size'] ?? 0);
            $inicio = max(0, $tamanho - $maxBytesPorArquivo);
            fseek($handle, $inicio);

            if ($inicio > 0) {
                fgets($handle);
            }

            $achada = null;

            foreach ($this->entradas($handle, $arquivo, $agulha) as $entrada) {
                $achada = $entrada;
            }

            fclose($handle);

            if ($achada !== null) {
                return $achada;
            }
        }

        return null;
    }

    private function entradas($handle, string $arquivo, ?string $somenteCom = null): \Generator
    {
        $atual = null;

        while (($linha = fgets($handle)) !== false) {
            $linha = rtrim($linha, "\r\n");

            if (preg_match(self::CABECALHO, $linha, $m)) {
                if ($atual !== null) {
                    yield $this->montar($atual, $arquivo);
                }

                $atual = ($somenteCom === null || str_contains($linha, $somenteCom))
                    ? ['data' => $m[1], 'ambiente' => $m[2], 'nivel' => strtoupper($m[3]), 'texto' => $m[4], 'trace' => '']
                    : null;

                continue;
            }

            if ($atual !== null && strlen($atual['trace']) < self::LIMITE_TRACE) {
                $atual['trace'] .= ($atual['trace'] === '' ? '' : "\n").$linha;
            }
        }

        if ($atual !== null) {
            yield $this->montar($atual, $arquivo);
        }
    }

    private function montar(array $bruta, string $arquivo): EntradaDeLog
    {
        $trace = $bruta['trace'];
        [$mensagem, $contexto] = $this->separarContexto(Sanitizador::limpar($bruta['texto']));

        if ($contexto === null && $trace !== '') {
            $junto = $bruta['texto'].'\n'.str_replace(["\r", "\n"], ['', '\n'], rtrim($trace));
            [$mensagemJunta, $contextoJunto] = $this->separarContexto(Sanitizador::limpar($junto));

            if ($contextoJunto !== null) {
                [$mensagem, $contexto] = [$mensagemJunta, $contextoJunto];
            }
        }

        if (is_string($contexto['exception'] ?? null) && str_contains($contexto['exception'], "\n[stacktrace]")) {
            [$excecao, $pilha] = explode("\n[stacktrace]", $contexto['exception'], 2);
            $contexto['exception'] = $excecao;
            $trace = trim($pilha);
        }

        return new EntradaDeLog(
            data: str_replace('T', ' ', $bruta['data']),
            ambiente: $bruta['ambiente'],
            nivel: $bruta['nivel'],
            mensagem: $mensagem,
            contexto: $contexto,
            trace: Sanitizador::limpar($trace),
            arquivo: $arquivo,
        );
    }

    private function separarContexto(string $texto): array
    {
        $texto = (string) preg_replace('/ \[\]$/', '', $texto);
        $posicao = -1;

        while (($posicao = strpos($texto, ' {', $posicao + 1)) !== false) {
            $json = json_decode(substr($texto, $posicao + 1), true);

            if (is_array($json)) {
                return [substr($texto, 0, $posicao), $json];
            }

            $semExtra = (string) preg_replace('/ (\{.*\}|\[\])$/U', '', substr($texto, $posicao + 1));
            $json = json_decode($semExtra, true);

            if (is_array($json)) {
                return [substr($texto, 0, $posicao), $json];
            }
        }

        return [$texto, null];
    }
}
