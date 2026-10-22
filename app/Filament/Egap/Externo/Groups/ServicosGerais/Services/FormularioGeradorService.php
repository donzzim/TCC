<?php

namespace App\Filament\Egap\Externo\Groups\ServicosGerais\Services;

use App\Filament\Egap\Externo\Groups\ServicosGerais\Enums\StatusFormulario;
use App\Filament\Egap\Externo\Groups\ServicosGerais\Enums\StatusResposta;
use App\Models\egap\ServicosGerais\Formulario;
use App\Models\egap\ServicosGerais\FormularioResposta;
use App\Models\egap\ServicosGerais\Pergunta;
use App\Models\egap\ServicosGerais\Periodo;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Gera os formulários de avaliação de um período (legado: avaforms.php).
 *
 * O legado tinha um loop de INSERTs diferente e hardcoded por tipo de
 * contrato (pesos 50/150/20 para 'li', 10 fixo para 'co'/'te', nenhuma
 * pergunta para 'tr'). Aqui isso vira uma única rotina data-driven a partir
 * do catálogo {@see Pergunta}, sem `if/elseif` por tipo.
 *
 * Uma vez gerados, os formulários de um período não são apagados nem
 * regerados (mesma regra do legado — ver aviso no modal "Criar Formulários").
 */
class FormularioGeradorService
{
    public function gerar(Periodo $periodo): int
    {
        if ($periodo->temFormularios()) {
            throw new RuntimeException('Os formulários deste período já foram gerados.');
        }

        $contrato = $periodo->contrato;

        if (! $contrato->usaQuestionario()) {
            throw new RuntimeException(
                "A geração de formulários para contratos do tipo \"{$contrato->tipo->getLabel()}\" ainda não está disponível nesta versão."
            );
        }

        $localidades = $contrato->localidadesAtivas()->get();

        if ($localidades->isEmpty()) {
            throw new RuntimeException('Não há localidades ativas cadastradas para este contrato.');
        }

        $perguntas = Pergunta::query()->paraTipo($contrato->tipo)->get();

        if ($perguntas->isEmpty()) {
            throw new RuntimeException("Não há perguntas cadastradas para o tipo \"{$contrato->tipo->getLabel()}\".");
        }

        return DB::connection('egap')->transaction(function () use ($localidades, $perguntas, $periodo): int {
            foreach ($localidades as $localidade) {
                $formulario = Formulario::query()->create([
                    'periodo_id' => $periodo->id,
                    'setor_id' => $localidade->setor_id,
                    'status' => StatusFormulario::Aberto,
                    'tem_falta_sem_substituicao' => false,
                    'nota_gestor' => 10,
                ]);

                $respostas = $perguntas->map(fn(Pergunta $pergunta): array => [
                    'formulario_id' => $formulario->id,
                    'pergunta_id' => $pergunta->id,
                    'status' => StatusResposta::Pendente->value,
                    'date_time' => now(),
                ])->all();

                FormularioResposta::query()->insert($respostas);
            }

            return $localidades->count();
        });
    }
}
