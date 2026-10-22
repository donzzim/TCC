<?php

namespace App\Http\Controllers\Egap\MobileApi;

use App\Filament\Egap\Auth\MobileEgapSession;
use App\Http\Controllers\Controller;
use App\Http\Resources\Egap\MobileApi\BemResource;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BensController extends Controller
{
    public const SITUACOES_ELEGIVEIS = [1, 7, 8];

    public function index(Request $request, MobileEgapSession $sessao): JsonResponse
    {
        $scope = $sessao->escopo();
        $perPage = $this->perPage($request);
        $search = trim((string) $request->query('search', ''));

        $paginator = $this->bensQuery()
            ->where('UnidadeJudiciaria', $scope['unidade_judiciaria'])
            ->where('Setor', $scope['setor'])
            ->whereIn('SituacaoBem', self::SITUACOES_ELEGIVEIS)
            ->when($search !== '', fn (Builder $query) => $this->applySearch($query, $search))
            ->orderBy('NumPatrimonio')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'data' => BemResource::collection($paginator->getCollection())->resolve($request),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function show(Request $request, MobileEgapSession $sessao, string $patrimonio): JsonResponse
    {
        $patrimonio = trim($patrimonio);
        $bem = $patrimonio === '' ? null : $this->buscarBemPorPatrimonio($patrimonio);

        if ($bem === null) {
            return response()->json(['message' => 'Bem não encontrado.'], 404);
        }

        $scope = $sessao->escopo();

        $bem->setAttribute('pertence_ao_setor', (int) $bem->UnidadeJudiciaria === $scope['unidade_judiciaria']
            && (int) $bem->Setor === $scope['setor']);

        return response()->json(['data' => (new BemResource($bem))->resolve($request)]);
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

        // Colunas int (NumPatrimonio, NumTomboSmarapd): sem nenhum dígito o MySQL
        // converte o texto para 0 e casaria bens com valor 0, então só entram
        // na busca quando o código tem dígito. Os campos de texto continuam.
        $temDigito = $somenteDigitos !== '';

        $query->where('TomboSmarapd', $codigo)
            ->orWhere('NumerodePatAnterior', $codigo);

        if ($temDigito) {
            $query
                ->orWhere('NumPatrimonio', $codigo)
                ->orWhere('NumPatrimonio', $semZeros)
                // orWhereRaw necessario: remove zeros a esquerda no valor armazenado
                // (TRIM LEADING), funcao que o query builder do Eloquent nao expõe.
                ->orWhereRaw("TRIM(LEADING '0' FROM NumPatrimonio) = ?", [$semZeros])
                ->orWhere('NumTomboSmarapd', $codigo);
        }

        if ($temDigito && $somenteDigitos !== $codigo) {
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

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 30);

        if ($perPage < 1) {
            return 30;
        }

        return min($perPage, 50);
    }
}
