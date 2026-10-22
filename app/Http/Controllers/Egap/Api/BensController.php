<?php

namespace App\Http\Controllers\Egap\Api;

use App\Http\Controllers\Controller;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use App\Models\egap\Patrimonio\BensMoveis\SituacaoBemMovel;
use App\Services\Egap\Mobile\ConferenciaBensService;
use App\Services\Egap\UsersConnectionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class BensController extends Controller
{
    private const SITUACOES_ELEGIVEIS = [1, 7, 8];

    public function dashboard(
        Request $request,
        UsersConnectionService $usersConnectionService,
        ConferenciaBensService $conferenciaBensService,
    ): JsonResponse {
        $scope = $this->scope($request, $usersConnectionService);

        if ($scope instanceof JsonResponse) {
            return $scope;
        }

        $baseQuery = $this->bensDoSetorBaseQuery($scope);
        $totalBens = (clone $baseQuery)->count();
        // selectRaw necessario nos dois blocos abaixo: agregacoes com SUM
        // condicional (CASE WHEN) e COUNT agrupado, sem equivalente fluente
        // no query builder do Eloquent.
        $financeiro = (clone $baseQuery)
            ->selectRaw('
                COALESCE(SUM(ValorAquisicao), 0) as valor_aquisicao,
                COALESCE(SUM(Valor), 0) as valor_atual,
                COALESCE(SUM(CASE WHEN ValorAquisicao IS NULL AND Valor IS NULL THEN 1 ELSE 0 END), 0) as sem_valor
            ')
            ->first();
        $situacoes = (clone $baseQuery)
            ->selectRaw('SituacaoBem as situacao_id, COUNT(*) as total')
            ->groupBy('SituacaoBem')
            ->orderByDesc('total')
            ->get();
        $situacaoLabels = SituacaoBemMovel::query()
            ->whereIn('id', $situacoes->pluck('situacao_id')->filter()->values())
            ->get(['id', 'descricao', 'situacao'])
            ->keyBy('id');

        try {
            $conferencia = $conferenciaBensService->atual($scope);
        } catch (Throwable) {
            $conferencia = null;
        }

        return response()->json([
            'scope' => $scope,
            'bens' => [
                'total' => $totalBens,
                'situacoes' => $situacoes
                    ->map(function ($row) use ($situacaoLabels): array {
                        $situacao = $situacaoLabels->get($row->situacao_id);

                        return [
                            'id' => $row->situacao_id,
                            'label' => $situacao?->descricao_completa ?? 'Sem situaÃ§Ã£o',
                            'total' => (int) $row->total,
                        ];
                    })
                    ->values(),
            ],
            'conferencia' => $conferencia,
            'financeiro' => [
                'valor_aquisicao' => (float) ($financeiro?->valor_aquisicao ?? 0),
                'valor_atual' => (float) ($financeiro?->valor_atual ?? 0),
                'sem_valor' => (int) ($financeiro?->sem_valor ?? 0),
                'avaliados' => $totalBens,
            ],
        ]);
    }

    public function index(Request $request, UsersConnectionService $usersConnectionService): JsonResponse
    {
        $scope = $this->scope($request, $usersConnectionService);

        if ($scope instanceof JsonResponse) {
            return $scope;
        }

        $perPage = $this->perPage($request);
        $search = trim((string) $request->query('search', ''));

        $paginator = $this->bensDoSetorQuery($scope)
            ->when($search !== '', fn (Builder $query) => $this->applySearch($query, $search))
            ->orderBy('NumPatrimonio')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (BemMovel $bem): array => $this->bemToArray($bem));

        return response()->json([
            'scope' => $scope,
            'total' => $paginator->total(),
            'bens' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function show(Request $request, UsersConnectionService $usersConnectionService, string $numPatrimonio): JsonResponse
    {
        $scope = $this->scope($request, $usersConnectionService);

        if ($scope instanceof JsonResponse) {
            return $scope;
        }

        $numPatrimonio = trim($numPatrimonio);

        if ($numPatrimonio === '') {
            return response()->json([
                'message' => 'Informe o NumPatrimonio do bem.',
            ], 422);
        }

        $bem = $this->buscarBemPorPatrimonio($numPatrimonio);

        if ($bem === null) {
            return response()->json([
                'message' => 'Bem não encontrado.',
                'patrimonio' => $numPatrimonio,
            ], 404);
        }

        return response()->json([
            'bem' => $this->bemToArray($bem),
            'scope' => [
                'belongs_to_user_scope' => (int) $bem->UnidadeJudiciaria === $scope['unidade_judiciaria']
                    && (int) $bem->Setor === $scope['setor'],
                'situacao_elegivel' => in_array((int) $bem->SituacaoBem, self::SITUACOES_ELEGIVEIS, true),
            ],
        ]);
    }

    /**
     * @return array{user_id:int|string|null,id_egap:int|string|null,setor:int,unidade_judiciaria:int}|JsonResponse
     */
    private function scope(Request $request, UsersConnectionService $usersConnectionService): array|JsonResponse
    {
        $mobileUser = $usersConnectionService->findByUser($request->user());

        if ($mobileUser === null) {
            return response()->json([
                'message' => 'Usuário sem vínculo válido para consulta de bens.',
            ], 403);
        }

        $setor = $this->filledInteger($mobileUser->setor());
        $unidadeJudiciaria = $this->filledInteger($mobileUser->unidadeJudiciaria());

        if ($setor === null || $unidadeJudiciaria === null) {
            return response()->json([
                'message' => 'Usuário sem lotação válida para consulta de bens.',
                'scope' => [
                    'user_id' => $mobileUser->id(),
                    'id_egap' => $mobileUser->idEgap(),
                    'setor' => $mobileUser->setor(),
                    'unidade_judiciaria' => $mobileUser->unidadeJudiciaria(),
                ],
            ], 422);
        }

        return [
            'user_id' => $mobileUser->id(),
            'id_egap' => $mobileUser->idEgap(),
            'setor' => $setor,
            'unidade_judiciaria' => $unidadeJudiciaria,
        ];
    }

    private function bensQuery(): Builder
    {
        return BemMovel::query()
            ->select([
                'id',
                'NumPatrimonio',
                'TomboSmarapd',
                'NumTomboSmarapd',
                'NumerodePatAnterior',
                'NumerodeSerie',
                'Descricao',
                'DescricaoResumidadoBem',
                'Marca',
                'Modelo',
                'TipodoBem',
                'EstadodeConservacao',
                'Voltagem',
                'SituacaoBem',
                'UnidadeJudiciaria',
                'Setor',
                'ComplementoSetor',
                'AndarSetor',
                'ValorAquisicao',
                'Valor',
                'DatadeIncorporacao',
                'DataCadastro',
                'DataBaixa',
                'ProcessoBaixa',
                'numero_processo',
                'nota_empenho',
                'nota_liquidacao',
                'data_liquidacao',
                'Observacao',
            ])
            ->with([
                'descricaoResumidaBemRef:id,Descricao',
                'marcaRef:id,descricao',
                'modeloRef:id,descricao',
                'situacaoBemRef:id,descricao,situacao',
                'unidadeJudiciariaRef:id,setor',
                'setorRef:id,setor,codigopai',
                'complementoSetorRef:id,descricao',
            ]);
    }

    private function bensDoSetorQuery(array $scope): Builder
    {
        return $this->bensQuery()
            ->where('UnidadeJudiciaria', $scope['unidade_judiciaria'])
            ->where('Setor', $scope['setor'])
            ->whereIn('SituacaoBem', self::SITUACOES_ELEGIVEIS);
    }

    private function bensDoSetorBaseQuery(array $scope): Builder
    {
        return BemMovel::query()
            ->where('UnidadeJudiciaria', $scope['unidade_judiciaria'])
            ->where('Setor', $scope['setor'])
            ->whereIn('SituacaoBem', self::SITUACOES_ELEGIVEIS);
    }

    private function applySearch(Builder $query, string $search): Builder
    {
        $numericSearch = preg_replace('/\D+/', '', $search) ?? '';

        return $query->where(function (Builder $query) use ($search, $numericSearch): void {
            $query
                ->where('Descricao', 'like', "%{$search}%")
                ->orWhere('NumerodePatAnterior', 'like', "%{$search}%")
                ->orWhere('NumerodeSerie', 'like', "%{$search}%")
                ->orWhereHas('descricaoResumidaBemRef', function (Builder $relationQuery) use ($search): void {
                    $relationQuery->where('Descricao', 'like', "%{$search}%");
                })
                ->orWhereHas('marcaRef', function (Builder $relationQuery) use ($search): void {
                    $relationQuery->where('descricao', 'like', "%{$search}%");
                })
                ->orWhereHas('modeloRef', function (Builder $relationQuery) use ($search): void {
                    $relationQuery->where('descricao', 'like', "%{$search}%");
                });

            if ($numericSearch !== '') {
                $query
                    ->orWhere('NumPatrimonio', (int) $numericSearch)
                    ->orWhere('NumTomboSmarapd', (int) $numericSearch)
                    ->orWhere('TomboSmarapd', 'like', "%{$numericSearch}%");
            }
        });
    }

    private function applyPatrimonioSearch(Builder $query, string $patrimonio): void
    {
        $codigo = preg_replace('/\s+/', '', trim($patrimonio)) ?? '';
        $somenteDigitos = preg_replace('/\D+/', '', $codigo) ?? '';
        $semZeros = ltrim($somenteDigitos !== '' ? $somenteDigitos : $codigo, '0') ?: '0';

        // orWhereRaw necessario: remove zeros a esquerda no valor armazenado
        // (TRIM LEADING), funcao que o query builder do Eloquent nao expõe.
        $query
            ->where('NumPatrimonio', $codigo)
            ->orWhere('NumPatrimonio', $semZeros)
            ->orWhereRaw("TRIM(LEADING '0' FROM NumPatrimonio) = ?", [$semZeros])
            ->orWhere('TomboSmarapd', $codigo)
            ->orWhere('NumTomboSmarapd', $codigo)
            ->orWhere('NumerodePatAnterior', $codigo);

        if ($somenteDigitos !== '' && $somenteDigitos !== $codigo) {
            $query
                ->orWhere('NumPatrimonio', $somenteDigitos)
                ->orWhere('TomboSmarapd', $somenteDigitos)
                ->orWhere('NumTomboSmarapd', $somenteDigitos)
                ->orWhere('NumerodePatAnterior', $somenteDigitos);
        }
    }

    private function buscarBemPorPatrimonio(string $numPatrimonio): ?BemMovel
    {
        return $this->bensQuery()
            ->where(function (Builder $query) use ($numPatrimonio): void {
                $this->applyPatrimonioSearch($query, $numPatrimonio);
            })
            ->first();
    }

    private function bemToArray(BemMovel $bem): array
    {
        return [
            'id' => $bem->id,
            'patrimonio' => $bem->NumPatrimonio,
            'patrimonio_anterior' => $bem->NumerodePatAnterior,
            'tombo_smarapd' => $bem->TomboSmarapd,
            'num_tombo_smarapd' => $bem->NumTomboSmarapd,
            'numero_serie' => $bem->NumerodeSerie,
            'descricao' => $bem->Descricao,
            'descricao_resumida' => $bem->descricaoResumidaBemRef?->Descricao,
            'marca' => $bem->marcaRef?->descricao,
            'modelo' => $bem->modeloRef?->descricao,
            'tipo_bem' => $bem->TipodoBem,
            'estado_conservacao' => $bem->EstadodeConservacao,
            'voltagem' => $bem->Voltagem,
            'situacao' => $bem->situacaoBemRef?->descricao_completa,
            'unidade_judiciaria' => [
                'id' => $bem->UnidadeJudiciaria,
                'nome' => $bem->unidadeJudiciariaRef?->setor,
            ],
            'setor' => [
                'id' => $bem->Setor,
                'nome' => $bem->setorRef?->setor,
            ],
            'complemento_setor' => [
                'id' => $bem->ComplementoSetor,
                'nome' => $bem->complementoSetorRef?->descricao,
            ],
            'andar_setor' => $bem->AndarSetor,
            'valor_aquisicao' => $bem->ValorAquisicao,
            'valor' => $bem->Valor,
            'data_incorporacao' => $bem->getRawOriginal('DatadeIncorporacao'),
            'data_cadastro' => $bem->getRawOriginal('DataCadastro'),
            'data_baixa' => $bem->getRawOriginal('DataBaixa'),
            'processo_baixa' => $bem->ProcessoBaixa,
            'numero_processo' => $bem->numero_processo,
            'nota_empenho' => $bem->nota_empenho,
            'nota_liquidacao' => $bem->nota_liquidacao,
            'data_liquidacao' => $bem->getRawOriginal('data_liquidacao'),
            'observacao' => $bem->Observacao,
        ];
    }

    private function filledInteger(int|string|null $value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        $integerValue = (int) $value;

        return $integerValue > 0 ? $integerValue : null;
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 30);

        if ($perPage < 1) {
            return 30;
        }

        return min($perPage, 50);
    }
}
