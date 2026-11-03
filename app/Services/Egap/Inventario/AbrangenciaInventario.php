<?php

namespace App\Services\Egap\Inventario;

use App\Models\egap\Cadastro\Setores;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
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
     * Cobertura de um único setor, com as mesmas regras de {@see setoresCobertos()}, sem
     * expandir a árvore inteira (que custa centenas de ms num inventário grande):
     * a linha de setor específico vale só para ele e vence; senão vale uma linha de
     * unidade inteira no próprio setor ou num ancestral (a de maior id, como lá).
     *
     * @return array{prazo: ?CarbonImmutable, unidade_inventariada_id: int}|null  null = fora do inventário
     */
    public static function coberturaDoSetor(Inventario $inventario, int $setorId): ?array
    {
        $candidatos = [$setorId, ...Setores::ancestraisIds($setorId)];

        $linhas = $inventario->unidadesInventariadas()
            ->whereIn('unidades', $candidatos)
            ->with('unidade')
            ->orderBy('id')
            ->get()
            ->filter(fn (InventarioUnidade $linha): bool => $linha->unidade !== null);

        $linha = $linhas->first(fn (InventarioUnidade $l): bool => ! $l->inventariaUnidadeInteira() && (int) $l->unidades === $setorId)
            ?? $linhas->filter(fn (InventarioUnidade $l): bool => $l->inventariaUnidadeInteira())->last();

        if ($linha === null) {
            return null;
        }

        $prazo = $linha->data_termino ?? $inventario->termino_inventario;

        return [
            'prazo' => $prazo === null ? null : CarbonImmutable::instance($prazo),
            'unidade_inventariada_id' => (int) $linha->getKey(),
        ];
    }

    /**
     * Marca como A INVENTARIAR os bens conferíveis dos setores cobertos. Rodar de novo não
     * apaga o que já foi conferido neste inventário (localizado, não localizado, divergente,
     * em transferência). Roda sozinho ao abrir o inventário e pelo botão "Atualizar situação".
     *
     * @return int quantos bens foram marcados
     */
    public static function marcarBensAInventariar(Inventario $inventario): int
    {
        $setores = static::setoresCobertos($inventario)->keys();

        if ($setores->isEmpty()) {
            return 0;
        }

        $id = (int) $inventario->getKey();
        $total = 0;

        foreach ($setores->chunk(1000) as $lote) {
            $total += BemMovel::query()
                ->whereIn('Setor', $lote->all())
                // só bens que entram na conferência (ativo, em trânsito, a inventariar)
                ->whereIn('SituacaoBem', [1, 7, 8])
                // condição escrita "pelo lado positivo": com NOT (a AND b), as linhas com
                // id_inventario e sit_inventario nulos davam NULL e ficavam de fora
                ->where(fn ($query) => $query
                    ->whereNull('id_inventario')
                    ->orWhere('id_inventario', '<>', $id)
                    ->orWhereNull('sit_inventario')
                    ->orWhereNotIn('sit_inventario', self::SITUACOES_CONFERIDAS))
                ->update([
                    'sit_inventario' => 'A INVENTARIAR',
                    'id_inventario' => $id,
                ]);
        }

        return $total;
    }

    /** Valores de sit_inventario que "Atualizar situação" não pode sobrescrever no mesmo inventário. */
    private const SITUACOES_CONFERIDAS = ['LOCALIZADO', 'NÃO LOCALIZADO', 'NAO LOCALIZADO', 'DIVERGENTE', 'EM TRANSFERENCIA', 'EM TRANSFERÊNCIA'];

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
