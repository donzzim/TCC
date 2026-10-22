<?php

namespace App\Services\Egap\Patrimonio;

use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use App\Models\egap\Patrimonio\BensMoveis\Termo;
use App\Models\egap\Patrimonio\BensMoveis\TransferenciaBemMovel;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class IncorporarBensService
{
    private const MAXIMO_BENS_POR_OPERACAO = 5000;

    private const CAMPOS_COPIADOS = [
        'SituacaoBem',
        'TomboSmarapd',
        'NumerodePatAnterior',
        'NumerodeSerie',
        'Produto',
        'ContaContabil',
        'DescricaoResumidadoBem',
        'Descricao',
        'Marca',
        'Modelo',
        'TipodoBem',
        'EstadodeConservacao',
        'Voltagem',
        'ClassificacaoInservivel',
        'UnidadeJudiciaria',
        'Setor',
        'ComplementoSetor',
        'AndarSetor',
        'Unidade',
        'DatadeIncorporacao',
        'Lote',
        'DatadeVencimento',
        'NumeracaoInicial',
        'NumeracaoFinal',
        'MesdeReferencia',
        'Fornecedor',
        'NotaFiscal',
        'FormaAquisicao',
        'SiglaUnidadeGestora',
        'Categoria',
        'Combustivel',
        'Placa',
        'Chassi',
        'Renavam',
        'VidaUtil',
        'DatadaReavaliacao',
        'ValordaReavaliacao',
        'ValordeMercado',
        'AcertoContabil',
        'UtilizacaodoBem',
        'VidaUtilEstimada',
        'EC',
        'PUB1',
        'PUB2',
        'PUV',
        'FR',
        'ValorReavaliado',
        'VidaUtilSIAFi',
        'UtilizacaodoBemMeses',
        'DepreciacaoMensal',
        'DepreciacaoAcumulada',
        'TestedeImpairment',
        'DataDisponibilizacao',
        'Observacao',
        'NumTomboSmarapd',
        'ProcessoBaixa',
        'DataBaixa',
        'ValorAquisicao',
        'AnoFabricacao',
        'AnoModelo',
        'ValorResidual',
        'Garantia',
        'numero_processo',
        'nota_empenho',
        'nota_liquidacao',
        'id_descricaodetalhada',
        'VidaUtilReavaliacao',
        'data_liquidacao',
    ];

    public function incorporar(int $bemReferenciaId, array $faixas, int $userId): IncorporarBensResultado
    {
        $numeros = $this->numerosDasFaixas($faixas);
        $ano = (int) now()->year;
        $connection = (new BemMovel)->getConnection();
        $lockName = "egap:incorporacao:termo:{$ano}";

        if (! $this->adquirirLock($connection, $lockName)) {
            throw new RuntimeException('Outra incorporação está em andamento. Tente novamente em alguns segundos.');
        }

        try {
            return $connection->transaction(function () use ($bemReferenciaId, $numeros, $userId, $ano): IncorporarBensResultado {
                $bemReferencia = BemMovel::query()
                    ->whereKey($bemReferenciaId)
                    ->lockForUpdate()
                    ->first();

                if (! $bemReferencia) {
                    throw ValidationException::withMessages([
                        'data.bem_referencia_id' => 'O bem de referência selecionado não existe mais.',
                    ]);
                }

                $this->validarNumerosDisponiveis($numeros);

                BemMovel::query()
                    ->whereKey($bemReferenciaId)
                    ->where('Setor', '<>', 1239)
                    ->where('ComplementoSetor', '<>', 1224)
                    ->update(['DataDisponibilizacao' => now()]);

                $bemReferencia->refresh();

                $numeroTermo = ((int) Termo::query()
                    ->where('ano_termo', $ano)
                    ->max('num_termo')) + 1;

                $termo = Termo::query()->create([
                    'date_time' => now(),
                    'num_termo' => $numeroTermo,
                    'ano_termo' => $ano,
                    'atualizado_em' => now(),
                    'atualizado_por' => $userId,
                ]);

                $termo->arquivoDigital()->create([
                    'date_time' => now(),
                    'atualizado_em' => now(),
                    'atualizado_por' => $userId,
                    'situacao' => 0,
                    'web' => 0,
                ]);

                $this->criarTransferencia($termo, $bemReferencia, $userId);

                foreach ($numeros as $numeroPatrimonio) {
                    $novoBem = BemMovel::query()->create(
                        $this->montarDadosDoNovoBem($bemReferencia, $numeroPatrimonio, $userId),
                    );

                    $this->criarTransferencia($termo, $novoBem, $userId);
                }

                return new IncorporarBensResultado(
                    termoId: (int) $termo->getKey(),
                    numeroTermo: $numeroTermo,
                    anoTermo: $ano,
                    quantidadeBens: count($numeros) + 1,
                );
            });
        } finally {
            $this->liberarLock($connection, $lockName);
        }
    }

    public function numerosDasFaixas(array $faixas): array
    {
        $numeros = [];

        foreach ($faixas as $indice => $faixa) {
            $inicio = (int) ($faixa['inicio'] ?? 0);
            $fim = (int) ($faixa['fim'] ?? $inicio);
            $fim = $fim ?: $inicio;

            if ($inicio < 1 || $fim < $inicio) {
                throw ValidationException::withMessages([
                    "data.faixas.{$indice}.fim" => 'A numeração final deve ser maior ou igual à inicial.',
                ]);
            }

            if (($fim - $inicio + 1) > self::MAXIMO_BENS_POR_OPERACAO) {
                throw ValidationException::withMessages([
                    "data.faixas.{$indice}.fim" => 'A faixa informada excede o limite de 5.000 bens por operação.',
                ]);
            }

            foreach (range($inicio, $fim) as $numero) {
                if (isset($numeros[$numero])) {
                    throw ValidationException::withMessages([
                        'data.faixas' => "O patrimônio {$numero} aparece mais de uma vez nas faixas informadas.",
                    ]);
                }

                $numeros[$numero] = $numero;

                if (count($numeros) > self::MAXIMO_BENS_POR_OPERACAO) {
                    throw ValidationException::withMessages([
                        'data.faixas' => 'O limite é de 5.000 novos bens por operação.',
                    ]);
                }
            }
        }

        return array_values($numeros);
    }

    private function validarNumerosDisponiveis(array $numeros): void
    {
        if ($numeros === []) {
            return;
        }

        $existentes = BemMovel::query()
            ->whereIn('NumPatrimonio', $numeros)
            ->orderBy('NumPatrimonio')
            ->lockForUpdate()
            ->pluck('NumPatrimonio')
            ->map(fn ($numero): int => (int) $numero)
            ->unique()
            ->values()
            ->all();

        if ($existentes !== []) {
            throw ValidationException::withMessages([
                'data.faixas' => 'Números de patrimônio já cadastrados: '.implode(', ', $existentes),
            ]);
        }
    }

    private function montarDadosDoNovoBem(BemMovel $bemReferencia, int $numeroPatrimonio, int $userId): array
    {
        $dados = [];

        foreach (self::CAMPOS_COPIADOS as $campo) {
            $dados[$campo] = $bemReferencia->getRawOriginal($campo);
        }

        return array_merge($dados, [
            'NumPatrimonio' => $numeroPatrimonio,
            'DataCadastro' => now(),
            'date_time' => now(),
            'Usuario' => $userId,
            'Valor' => $bemReferencia->getRawOriginal('ValorAquisicao'),
            'situacao_contabil' => 'LOCALIZADO',
            'data_situacao_contabil' => now(),
        ]);
    }

    private function criarTransferencia(
        Termo $termo,
        BemMovel $bem,
        int $userId,
    ): TransferenciaBemMovel {
        return $termo->transferencias()->create([
            'date_time' => now(),
            'NumPatrimonio' => $bem->getKey(),
            'UnidadeAtual' => $bem->UnidadeJudiciaria,
            'SetorAtual' => $bem->Setor,
            'ComplementoAtual' => $bem->ComplementoSetor,
            'AndarAtual' => $bem->AndarSetor,
            'Usuario' => $userId,
        ]);
    }

    private function adquirirLock(ConnectionInterface $connection, string $lockName): bool
    {
        $resultado = $connection->selectOne('SELECT GET_LOCK(?, 10) AS acquired', [$lockName]);

        return (int) ($resultado->acquired ?? 0) === 1;
    }

    private function liberarLock(ConnectionInterface $connection, string $lockName): void
    {
        try {
            $connection->selectOne('SELECT RELEASE_LOCK(?) AS released', [$lockName]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
