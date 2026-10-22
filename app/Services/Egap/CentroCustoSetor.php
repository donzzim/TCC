<?php

namespace App\Services\Egap;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Resolve o centro de custo de um setor usando apenas a tabela `setores`.
 *
 * Substitui `egap_cad_centrocusto`, que guardava o código no formato contábil
 * antigo (`3.1.05.1.00.00.000`) e casava com `egap_mat_setores.centrocusto`.
 * Em `setores` o centro de custo é um código hierárquico de 14 dígitos
 * (`31005000000000`), e os dois domínios não se cruzam: o join antigo casava
 * 1.088 linhas, o mesmo join contra `setores` casa zero.
 *
 * Como funciona:
 *   - os 5 primeiros dígitos identificam o centro de custo (79 distintos, na
 *     mesma ordem de grandeza dos 84 do cadastro antigo);
 *   - o setor cujo código termina em 9 zeros é a raiz daquele centro de custo,
 *     e o nome dele vira o rótulo (`31005000000000` -> PRESIDÊNCIA);
 *   - setor sem `centrocusto` próprio herda subindo por `codigopai`.
 */
class CentroCustoSetor
{
    /** Quantos níveis de `codigopai` subir procurando um centro de custo. */
    private const NIVEIS = 3;

    /**
     * Adiciona os LEFT JOINs que permitem resolver o centro de custo.
     *
     * Espera que a query já tenha um join com `setores` sob o alias informado.
     * Cria os aliases `{$alias}_cc1..3` (ancestrais) e `{$alias}_cc` (rótulo).
     */
    public static function aplicarJoins(
        QueryBuilder|EloquentBuilder $query,
        string $alias = 's',
        bool $comRotulo = true,
    ): QueryBuilder|EloquentBuilder {
        $anterior = $alias;

        for ($nivel = 1; $nivel <= self::NIVEIS; $nivel++) {
            $atual = "{$alias}_cc{$nivel}";

            $query->leftJoin("setores as {$atual}", function ($join) use ($atual, $anterior) {
                $join->on("{$atual}.id", '=', "{$anterior}.codigopai")
                    ->on("{$atual}.id", '<>', "{$anterior}.id");
            });

            $anterior = $atual;
        }

        if (! $comRotulo) {
            return $query;
        }

        return $query->leftJoinSub(
            self::rotulos(),
            "{$alias}_cc",
            fn ($join) => $join->on("{$alias}_cc.codigo", '=', DB::raw(self::codigoSql($alias))),
        );
    }

    /**
     * Um rótulo por centro de custo, deduplicado.
     *
     * Escolhe, dentro do prefixo, o setor de menor `centrocusto` — a raiz,
     * quando existe. O desempate é necessário porque vários setores podem
     * carregar o mesmo código, e a ordem é:
     *
     *   1. unidade organizacional (`id` = `codigopai`) vem primeiro;
     *   2. depois o menor `id`, que na prática é o cadastro mais antigo;
     *   3. só então o nome.
     *
     * Sem o passo 1 o centro 31000 sairia como "LABORATORIO DE INOVACAO..." em
     * vez de "TRIBUNAL DE JUSTIÇA DO ESPÍRITO SANTO". Sem o passo 2 o 31005
     * sairia como "NÚCLEO DE JUSTIÇA 4.0 - COMBATE AO CRIME..." em vez de
     * "PRESIDÊNCIA", porque a correção de hierarquia copiou o código do pai
     * para os filhos e criou 11 empates no mesmo prefixo.
     *
     * O GROUP BY garante uma linha por código, então o join nunca multiplica
     * linhas — há prefixo com mais de uma raiz, como o 33043 (COMARCA DE SANTA
     * LEOPOLDINA e COMISSÃO DE ORGANIZAÇÃO JUDICIÁRIA).
     */
    public static function rotulos(): QueryBuilder
    {
        return DB::connection('emes')
            ->table('setores')
            ->selectRaw('LEFT(centrocusto, 5) AS codigo')
            ->selectRaw(
                "SUBSTRING_INDEX(
                     MIN(CONCAT(
                         centrocusto,
                         IF(id = codigopai, '0', '1'),
                         LPAD(id, 10, '0'),
                         '|', setor
                     )),
                     '|', -1
                 ) AS descricao"
            )
            ->whereRaw("centrocusto REGEXP '^[0-9]{14}$'")
            ->groupByRaw('LEFT(centrocusto, 5)');
    }

    /** Expressão SQL do código de 14 dígitos, já resolvido pela hierarquia. */
    public static function centroCustoSql(string $alias = 's'): string
    {
        $colunas = [$alias];

        for ($nivel = 1; $nivel <= self::NIVEIS; $nivel++) {
            $colunas[] = "{$alias}_cc{$nivel}";
        }

        return 'COALESCE(' . implode(', ', array_map(
            static fn (string $a): string => "{$a}.centrocusto",
            $colunas,
        )) . ')';
    }

    /** Expressão SQL do código do centro de custo (5 dígitos). */
    public static function codigoSql(string $alias = 's'): string
    {
        return 'LEFT(' . self::centroCustoSql($alias) . ', 5)';
    }

    /** Expressão SQL do rótulo do centro de custo. */
    public static function descricaoSql(string $alias = 's'): string
    {
        return "{$alias}_cc.descricao";
    }

    /**
     * Centros de custo existentes, como objetos {codigo, descricao}.
     *
     * Substitui a listagem de `egap_cad_centrocusto`.
     *
     * @return Collection<int, object>
     */
    public static function listar(?string $apenasCodigo = null): Collection
    {
        return self::rotulos()
            ->when($apenasCodigo, fn ($q) => $q->havingRaw('codigo = ?', [$apenasCodigo]))
            ->orderBy('codigo')
            ->get();
    }

    /**
     * Ids dos setores de um centro de custo, considerando a hierarquia: inclui
     * os que não têm `centrocusto` próprio e herdam de um ancestral.
     *
     * @return list<int>
     */
    public static function setorIds(string $codigo): array
    {
        $query = DB::connection('emes')->table('setores as s');

        self::aplicarJoins($query, 's', comRotulo: false);

        return $query
            ->whereRaw(self::codigoSql('s') . ' = ?', [$codigo])
            ->pluck('s.id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * Mapa id do setor => código do centro de custo, numa única consulta.
     *
     * Equivale a chamar setorIds() para cada centro de custo, sem o N+1:
     * relatórios que percorrem todos os centros agrupam por setor e somam
     * por centro em PHP.
     *
     * @return array<int, string>
     */
    public static function mapaSetores(?string $apenasCodigo = null): array
    {
        $query = DB::connection('emes')->table('setores as s');

        self::aplicarJoins($query, 's', comRotulo: false);

        return $query
            ->whereRaw(self::codigoSql('s') . ' IS NOT NULL')
            ->when($apenasCodigo, fn ($q) => $q->whereRaw(self::codigoSql('s') . ' = ?', [$apenasCodigo]))
            ->selectRaw('s.id, ' . self::codigoSql('s') . ' AS codigo')
            ->pluck('codigo', 's.id')
            ->mapWithKeys(static fn ($codigo, $id): array => [(int) $id => (string) $codigo])
            ->all();
    }

    /**
     * Opções para o filtro dos relatórios: código => "código - nome".
     *
     * @return array<string, string>
     */
    public static function opcoesFiltro(): array
    {
        return self::listar()
            ->mapWithKeys(fn (object $c): array => [
                $c->codigo => trim("{$c->codigo} - {$c->descricao}"),
            ])
            ->all();
    }
}
