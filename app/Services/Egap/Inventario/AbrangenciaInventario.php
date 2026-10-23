<?php

namespace App\Services\Egap\Inventario;

use App\Models\egap\Cadastro\Setores;
use App\Models\egap\Patrimonio\BensMoveis\Inventario;
use App\Models\egap\Patrimonio\BensMoveis\InventarioUnidade;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Quais setores um inventário cobre (em cascata, em todos os níveis) e com qual prazo.
 */
class AbrangenciaInventario
{
    /**
     * @return Collection<int, array{prazo: ?CarbonImmutable, unidade_inventariada_id: int}>
     */
    public static function setoresCobertos(Inventario $inventario): Collection
    {
        $cobertos = collect();
        $especificos = [];

        $linhas = $inventario->unidadesInventariadas()->with('unidade')->get();

        // unidades inteiras primeiro; a linha de setor específico sobrescreve depois
        $ordenadas = $linhas->sortBy(fn (InventarioUnidade $linha): int => $linha->inventariaUnidadeInteira() ? 0 : 1);

        foreach ($ordenadas as $linha) {
            $setor = $linha->unidade;

            if ($setor === null) {
                continue;
            }

            $prazo = $linha->data_termino ?? $inventario->termino_inventario;
            $prazo = $prazo === null ? null : CarbonImmutable::instance($prazo);
            $valor = ['prazo' => $prazo, 'unidade_inventariada_id' => (int) $linha->getKey()];

            if ($linha->inventariaUnidadeInteira()) {
                $ids = [(int) $setor->id, ...array_map('intval', Setores::descendentesIds((int) $setor->id))];

                foreach ($ids as $id) {
                    if (! isset($especificos[$id])) {
                        $cobertos->put($id, $valor);
                    }
                }

                continue;
            }

            $especificos[(int) $setor->id] = true;
            $cobertos->put((int) $setor->id, $valor);
        }

        return $cobertos;
    }

    /**
     * @param  array<int, int>  $unidadeIds
     * @param  array<int, array{setor_id: int, incluir_filhos: bool}>  $setores
     * @return int quantas linhas foram criadas
     */
    public static function registrar(Inventario $inventario, array $unidadeIds, array $setores): int
    {
        $unidadeIds = array_values(array_unique(array_map('intval', $unidadeIds)));

        $jaTem = array_flip($inventario->unidadesInventariadas()->pluck('unidades')->map(fn ($id): int => (int) $id)->all());

        $cobertosPelasUnidades = [];
        foreach ($unidadeIds as $unidadeId) {
            $cobertosPelasUnidades += array_flip(array_map('intval', Setores::descendentesIds($unidadeId)));
        }

        $alvos = array_fill_keys($unidadeIds, true);

        foreach ($setores as $setor) {
            $id = (int) $setor['setor_id'];
            $alvos[$id] = true;

            if (! empty($setor['incluir_filhos'])) {
                foreach (Setores::descendentesIds($id) as $filho) {
                    $alvos[(int) $filho] = true;
                }
            }
        }

        $criadas = 0;

        foreach (array_keys($alvos) as $id) {
            $ehUnidadeEscolhida = in_array($id, $unidadeIds, true);

            if (isset($jaTem[$id]) || (! $ehUnidadeEscolhida && isset($cobertosPelasUnidades[$id]))) {
                continue;
            }

            $inventario->unidadesInventariadas()->create([
                'unidades' => $id,
                'data_inicio' => $inventario->inicio_inventario,
                'data_termino' => $inventario->termino_inventario,
                'dias' => $inventario->dias,
                'situacao' => Inventario::SITUACAO_A_INVENTARIAR,
            ]);

            $criadas++;
        }

        return $criadas;
    }
}
