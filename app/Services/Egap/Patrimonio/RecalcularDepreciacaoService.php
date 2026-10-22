<?php

namespace App\Services\Egap\Patrimonio;

use App\Models\egap\Cadastro\ElementoDespesa;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use App\Models\egap\Patrimonio\BensMoveis\Depreciacao;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class RecalcularDepreciacaoService
{
    public function recalcular(BemMovel $bem): RecalcularDepreciacaoResultado
    {
        $connection = $bem->getConnection();
        $connectionName = $bem->getConnectionName();

        return $connection->transaction(function () use ($bem, $connectionName): RecalcularDepreciacaoResultado {
            $bem = BemMovel::on($connectionName)
                ->whereKey($bem->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $this->dataValida($bem->getRawOriginal('DataDisponibilizacao'))) {
                throw ValidationException::withMessages([
                    'record' => 'O bem não possui Data de Disponibilização informada.',
                ]);
            }

            $produto = ElementoDespesa::on($connectionName)->find($bem->Produto);

            if (! $produto) {
                throw ValidationException::withMessages([
                    'record' => 'O elemento de despesa (produto) vinculado a este bem não foi encontrado.',
                ]);
            }

            Depreciacao::on($connectionName)->where('patrimonio', $bem->getKey())->delete();

            $usaRegraNova = ! optional($bem->DatadeIncorporacao)->lt('2015-01-01');

            if ($usaRegraNova) {
                $dataCalculo = Carbon::parse($bem->getRawOriginal('DataDisponibilizacao'))->startOfMonth();
                $valor = (float) $bem->ValorAquisicao;
                $vidaUtil = (int) $produto->VidaUtil;
            } else {
                $dataCalculo = Carbon::parse($bem->getRawOriginal('DatadaReavaliacao'))->startOfMonth();
                $valor = (float) $bem->ValordaReavaliacao;
                $vidaUtil = (int) $bem->VidaUtil;
            }

            $percentualResidual = (float) $produto->ValorResidual;
            $valorResidual = $valor * ($percentualResidual / 100);
            $depreciacaoMensal = $vidaUtil > 0 ? ($valor - $valorResidual) / $vidaUtil : 0.0;
            $depreciacaoAcumulada = 0.0;
            $valorLiquidoContabil = $valor;

            $seq = 1;

            for ($i = $vidaUtil; $i >= 0; $i--) {
                Depreciacao::on($connectionName)->create([
                    'patrimonio' => $bem->getKey(),
                    'item' => $seq,
                    'data_calculo' => $dataCalculo->toDateString(),
                    'valor' => $valor,
                    'vida_util' => $i,
                    'valor_residual' => $valorResidual,
                    'depreciacao_mensal' => $depreciacaoMensal,
                    'depreciacao_acumulada' => $depreciacaoAcumulada,
                    'valor_liquido_contabil' => $valorLiquidoContabil,
                ]);

                $seq++;
                $dataCalculo = $dataCalculo->copy()->addMonthNoOverflow()->startOfMonth();
                $depreciacaoAcumulada = ($seq - 1) * $depreciacaoMensal;
                $valorLiquidoContabil = $valor - $depreciacaoAcumulada;
            }

            $this->atualizarPatrimonio($bem, $produto, $connectionName);

            return new RecalcularDepreciacaoResultado(
                patrimonioId: (int) $bem->getKey(),
                parcelasGeradas: $vidaUtil + 1,
                depreciacaoMensal: $depreciacaoMensal,
                valorResidual: $valorResidual,
            );
        });
    }

    private function atualizarPatrimonio(BemMovel $bem, ElementoDespesa $produto, string $connectionName): void
    {
        $inicioMes = now()->startOfMonth()->toDateString();
        $fimMes = now()->endOfMonth()->toDateString();

        $calculoDoMes = Depreciacao::on($connectionName)
            ->where('patrimonio', $bem->getKey())
            ->whereBetween('data_calculo', [$inicioMes, $fimMes])
            ->first();

        if (! $calculoDoMes) {
            return;
        }

        $bem->forceFill([
            'VidaUtilSIAFi' => $produto->VidaUtil,
            'UtilizacaodoBemMeses' => $produto->VidaUtil - $calculoDoMes->vida_util,
            'DepreciacaoMensal' => $calculoDoMes->depreciacao_mensal,
            'DepreciacaoAcumulada' => $calculoDoMes->depreciacao_acumulada,
            'Valor' => $calculoDoMes->valor_liquido_contabil,
            'ValorResidual' => $calculoDoMes->valor_residual,
            'AcertoContabil' => (float) $bem->ValordaReavaliacao - (float) $bem->ValorAquisicao,
            'VidaUtil' => $calculoDoMes->vida_util,
        ]);

        $bem->save();
    }

    private function dataValida(?string $data): bool
    {
        return $data !== null && $data !== '' && $data !== '0000-00-00 00:00:00';
    }
}
