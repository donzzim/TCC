<?php

namespace App\Services\Egap\Mobile;

use App\Models\egap\Almoxarifado\FasePedido;
use App\Models\egap\Almoxarifado\ItemPedido;
use App\Models\egap\Almoxarifado\MovimentacaoEstoque;
use App\Models\egap\Almoxarifado\Pedidos;
use App\Models\egap\Cadastro\ComplementoSetor;
use App\Models\egap\Cadastro\DescricaoDetalhada;
use App\Models\egap\Cadastro\DescricaoResumida;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use App\Services\Egap\Almoxarifado\VisibilidadeMaterial;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PedidosMobileService
{
    private const STATUS_EM_ANALISE = 6;

    private const SETOR_ALMOXARIFADO = 799;

    private const SETOR_PATRIMONIO = 1239;

    /**
     * @param  array{setor:int}  $scope
     * @return array<int, array{id:int, descricao:string}>
     */
    public function complementos(array $scope): array
    {
        $complementosDoSetor = ComplementoSetor::query()
            ->select(['egap_mat_complementosetor.id', 'egap_mat_complementosetor.descricao'])
            ->whereIn('egap_mat_complementosetor.id', BemMovel::query()
                ->select('ComplementoSetor')
                ->where('Setor', $scope['setor'])
                ->whereNotNull('ComplementoSetor')
                ->distinct())
            ->orderBy('descricao')
            ->get();

        $complementos = $complementosDoSetor->isNotEmpty()
            ? $complementosDoSetor
            : ComplementoSetor::query()
                ->select(['id', 'descricao'])
                ->orderBy('descricao')
                ->limit(200)
                ->get();

        return $complementos
            ->map(fn (ComplementoSetor $complemento): array => [
                'id' => (int) $complemento->id,
                'descricao' => (string) $complemento->descricao,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array{unidade_judiciaria:int}  $scope
     */
    public function materiais(array $scope, string $tipo, ?string $search, int $perPage): LengthAwarePaginator
    {
        return match ($tipo) {
            'consumo' => $this->materiaisConsumo($scope, $search, $perPage),
            'permanente' => $this->materiaisPermanentes($scope, $search, $perPage),
            default => throw ValidationException::withMessages([
                'tipo' => 'Tipo de pedido invalido.',
            ]),
        };
    }

    /**
     * @param  array{user_id:int,id_egap:int,setor:int,unidade_judiciaria:int}  $scope
     * @param  array<string, mixed>  $payload
     */
    public function criarPedido(array $scope, array $payload): Pedidos
    {
        $tipo = (string) ($payload['tipo'] ?? '');
        $itens = $payload['itens'] ?? [];

        if (! in_array($tipo, ['consumo', 'permanente'], true)) {
            throw ValidationException::withMessages([
                'tipo' => 'Tipo de pedido invalido.',
            ]);
        }

        if (! is_array($itens) || $itens === []) {
            throw ValidationException::withMessages([
                'itens' => 'Adicione ao menos um material ao pedido.',
            ]);
        }

        $complementoId = $this->filledInteger($payload['complemento_setor_id'] ?? null);

        if ($complementoId === null || ! ComplementoSetor::query()->whereKey($complementoId)->exists()) {
            throw ValidationException::withMessages([
                'complemento_setor_id' => 'Selecione um complemento de setor válido.',
            ]);
        }

        $justificativaGeral = trim((string) ($payload['justificativa'] ?? ''));

        if ($tipo === 'consumo' && $justificativaGeral === '') {
            throw ValidationException::withMessages([
                'justificativa' => 'Informe a justificativa do pedido.',
            ]);
        }

        return DB::connection('egap')->transaction(function () use ($scope, $tipo, $itens, $complementoId, $justificativaGeral): Pedidos {
            $pedido = Pedidos::query()->create([
                'date_time' => now(),
                'Solicitante' => $scope['id_egap'],
                'UnidadeJudiciaria' => $scope['unidade_judiciaria'],
                'Setor' => $scope['setor'],
                'idSituacao' => self::STATUS_EM_ANALISE,
                'setor_responsavel' => $tipo === 'consumo'
                    ? self::SETOR_ALMOXARIFADO
                    : self::SETOR_PATRIMONIO,
                'Observacao' => $tipo === 'consumo' ? $justificativaGeral : null,
                'ComplementoSetor' => $complementoId,
            ]);

            $this->registrarFase(
                $scope,
                $pedido,
                null,
                'Pedido criado via aplicativo mobile.'
            );

            foreach ($itens as $index => $itemPayload) {
                if (! is_array($itemPayload)) {
                    throw ValidationException::withMessages([
                        "itens.{$index}" => 'Item do pedido invalido.',
                    ]);
                }

                $item = $tipo === 'consumo'
                    ? $this->criarItemConsumo($pedido, $itemPayload, $index)
                    : $this->criarItemPermanente($pedido, $itemPayload, $index);

                $this->registrarFase(
                    $scope,
                    $pedido,
                    $item,
                    'Item incluído no pedido via aplicativo mobile.'
                );
            }

            return $pedido->load(['itens', 'complementoSetor', 'situacao']);
        });
    }

    /**
     * @param  array{user_id:int,id_egap:int,setor:int,unidade_judiciaria:int}  $scope
     */
    public function pedidosDoUsuario(array $scope, int $perPage): LengthAwarePaginator
    {
        return Pedidos::query()
            ->with(['situacao', 'itens.situacaoRef', 'itens.materialRef', 'itens.descricaoDetalhadaRef', 'complementoSetor'])
            ->where('Solicitante', $scope['id_egap'])
            ->where('Setor', $scope['setor'])
            ->orderByDesc('id')
            ->paginate($perPage)
            ->through(fn (Pedidos $pedido): array => $this->pedidoToArray($pedido));
    }

    /**
     * Pedidos do usuário no setor (mesmo filtro de pedidosDoUsuario) ainda em análise.
     *
     * @param  array{id_egap:int|null,setor:int}  $scope
     */
    public function contarAbertos(array $scope): int
    {
        if (empty($scope['id_egap'])) {
            return 0;
        }

        return Pedidos::query()
            ->where('Solicitante', $scope['id_egap'])
            ->where('Setor', $scope['setor'])
            ->where('idSituacao', self::STATUS_EM_ANALISE)
            ->count();
    }

    /**
     * @param  array{unidade_judiciaria:int}  $scope
     */
    private function materiaisConsumo(array $scope, ?string $search, int $perPage): LengthAwarePaginator
    {
        $visibilidade = $this->visibilidadesPermitidas($scope['unidade_judiciaria']);

        $paginator = DescricaoDetalhada::query()
            ->with([
                'descricao_resumida_text:id,Descricao,id_tipo_material,imagem',
                'unidadeMedida:id,Unidade,Sigla',
            ])
            ->select(['id', 'descricao_resumida', 'descricao_detalhada', 'imagem', 'visibilidade', 'unidade_medida'])
            ->whereIn('visibilidade', $visibilidade)
            ->whereHas('descricao_resumida_text', fn (Builder $query): Builder => $query->where('id_tipo_material', 'C'))
            ->when($search !== null && trim($search) !== '', function (Builder $query) use ($search): void {
                $query->where('descricao_detalhada', 'like', '%'.trim($search).'%');
            })
            ->orderBy('descricao_detalhada')
            ->paginate($perPage)
            ->withQueryString();

        $materialIds = collect($paginator->items())->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $estoques = MovimentacaoEstoque::estoquesAtuais($materialIds);

        return $paginator->through(fn (DescricaoDetalhada $material): array => $this->materialConsumoToArray($material, $estoques));
    }

    /**
     * @param  array{unidade_judiciaria:int}  $scope
     */
    private function materiaisPermanentes(array $scope, ?string $search, int $perPage): LengthAwarePaginator
    {
        $visibilidade = $this->visibilidadesPermitidas($scope['unidade_judiciaria']);

        $paginator = DescricaoResumida::query()
            ->select(['id', 'Descricao', 'imagem', 'visibilidade'])
            ->whereIn('visibilidade', $visibilidade)
            ->where('id_tipo_material', 'P')
            ->when($search !== null && trim($search) !== '', function (Builder $query) use ($search): void {
                $query->where('Descricao', 'like', '%'.trim($search).'%');
            })
            ->orderBy('Descricao')
            ->paginate($perPage)
            ->withQueryString();

        $materialIds = collect($paginator->items())->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $precos = $this->precosPermanentes($materialIds);

        return $paginator->through(fn (DescricaoResumida $material): array => $this->materialPermanenteToArray($material, $precos));
    }

    /**
     * @param  array<int>  $materialIds
     * @return Collection<int, float>
     */
    private function precosPermanentes(array $materialIds): Collection
    {
        if ($materialIds === []) {
            return collect();
        }

        return BemMovel::query()
            ->select([
                'DescricaoResumidadoBem',
                'ValorAquisicao',
                'ValordaReavaliacao',
                'DatadeIncorporacao',
                'DatadaReavaliacao',
            ])
            ->whereIn('DescricaoResumidadoBem', $materialIds)
            ->where('ValorAquisicao', '<>', 0)
            ->orderByDesc('DatadaReavaliacao')
            ->get()
            ->groupBy('DescricaoResumidadoBem')
            ->map(function (Collection $bens): float {
                /** @var BemMovel $bem */
                $bem = $bens->first();
                $incorporacao = $bem->DatadeIncorporacao;
                $reavaliacao = $bem->DatadaReavaliacao
                    ? Carbon::parse($bem->DatadaReavaliacao)
                    : null;

                if ($incorporacao && $reavaliacao && $incorporacao->greaterThan($reavaliacao)) {
                    return (float) ($bem->ValorAquisicao ?? 0);
                }

                return (float) ($bem->ValordaReavaliacao ?? $bem->ValorAquisicao ?? 0);
            });
    }

    /**
     * @param  Collection<int, MovimentacaoEstoque>  $estoques
     */
    private function materialConsumoToArray(DescricaoDetalhada $material, Collection $estoques): array
    {
        /** @var MovimentacaoEstoque|null $estoque */
        $estoque = $estoques->get($material->id);

        return [
            'id' => (int) $material->id,
            'tipo' => 'consumo',
            'descricao' => (string) $material->descricao_detalhada,
            'descricao_resumida_id' => (int) ($material->descricao_resumida ?? 0),
            'descricao_resumida' => (string) ($material->descricao_resumida_text?->Descricao ?? ''),
            'unidade' => (string) ($material->unidadeMedida?->Unidade ?? $material->unidadeMedida?->Sigla ?? 'Unidade'),
            'preco_medio' => (float) ($estoque?->preco_medio_estoque ?? 0),
            'quantidade_estoque' => (int) ($estoque?->quantidade_estoque ?? 0),
            'disponivel' => (int) ($estoque?->quantidade_estoque ?? 0) > 0,
            'imagem_url' => ImagemMaterial::url($material->imagem, $material->descricao_resumida_text?->imagem),
        ];
    }

    /**
     * @param  Collection<int, float>  $precos
     */
    private function materialPermanenteToArray(DescricaoResumida $material, Collection $precos): array
    {
        return [
            'id' => (int) $material->id,
            'tipo' => 'permanente',
            'descricao' => (string) $material->Descricao,
            'unidade' => 'Unidade',
            'preco_medio' => (float) ($precos->get($material->id, 0)),
            'quantidade_estoque' => null,
            'disponivel' => true,
            'imagem_url' => ImagemMaterial::url($material->imagem),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function criarItemConsumo(Pedidos $pedido, array $payload, int $index): ItemPedido
    {
        $materialId = $this->filledInteger($payload['material_id'] ?? null);
        $quantidade = $this->filledInteger($payload['quantidade'] ?? null);

        if ($materialId === null || $quantidade === null || $quantidade < 1) {
            throw ValidationException::withMessages([
                "itens.{$index}.quantidade" => 'Informe material e quantidade maior que zero.',
            ]);
        }

        /** @var DescricaoDetalhada|null $material */
        $material = DescricaoDetalhada::query()
            ->with('descricao_resumida_text')
            ->find($materialId);

        if (! $material || $material->descricao_resumida_text?->id_tipo_material !== 'C') {
            throw ValidationException::withMessages([
                "itens.{$index}.material_id" => 'Material de consumo invalido.',
            ]);
        }

        $estoque = MovimentacaoEstoque::estoquesAtuais([$materialId])->get($materialId);

        return $pedido->itens()->create([
            'date_time' => now(),
            'QuantidadeMaterial' => $quantidade,
            'QuantidadeMaterialAtendida' => 0,
            'material' => $material->descricao_resumida,
            'DescricaoDetalhada' => $material->id,
            'situacao' => self::STATUS_EM_ANALISE,
            'valor_material' => (float) ($estoque?->preco_medio_estoque ?? 0),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function criarItemPermanente(Pedidos $pedido, array $payload, int $index): ItemPedido
    {
        $materialId = $this->filledInteger($payload['material_id'] ?? null);
        $quantidade = $this->filledInteger($payload['quantidade'] ?? null);
        $tipoAtendimento = (string) ($payload['tipo_atendimento'] ?? 'adicao');
        $justificativa = trim((string) ($payload['justificativa'] ?? ''));
        $patrimonioSubstituido = trim((string) ($payload['patrimonio_substituido'] ?? ''));

        if ($materialId === null || $quantidade === null || $quantidade < 1) {
            throw ValidationException::withMessages([
                "itens.{$index}.quantidade" => 'Informe material e quantidade maior que zero.',
            ]);
        }

        if (! in_array($tipoAtendimento, ['adicao', 'substituicao'], true)) {
            throw ValidationException::withMessages([
                "itens.{$index}.tipo_atendimento" => 'Selecione adição ou substituição.',
            ]);
        }

        if ($justificativa === '') {
            throw ValidationException::withMessages([
                "itens.{$index}.justificativa" => 'Informe a justificativa do material.',
            ]);
        }

        if ($tipoAtendimento === 'substituicao' && $patrimonioSubstituido === '') {
            throw ValidationException::withMessages([
                "itens.{$index}.patrimonio_substituido" => 'Informe o patrimônio que será substituído.',
            ]);
        }

        /** @var DescricaoResumida|null $material */
        $material = DescricaoResumida::query()
            ->where('id_tipo_material', 'P')
            ->find($materialId);

        if (! $material) {
            throw ValidationException::withMessages([
                "itens.{$index}.material_id" => 'Material permanente invalido.',
            ]);
        }

        $preco = (float) $this->precosPermanentes([$materialId])->get($materialId, 0);

        return $pedido->itens()->create([
            'date_time' => now(),
            'QuantidadeMaterial' => $quantidade,
            'QuantidadeMaterialAtendida' => 0,
            'material' => $material->id,
            'situacao' => self::STATUS_EM_ANALISE,
            'justificativa' => $this->formatarJustificativaPermanente($tipoAtendimento, $justificativa, $patrimonioSubstituido),
            'valor_material' => $preco,
        ]);
    }

    private function registrarFase(array $scope, Pedidos $pedido, ?ItemPedido $item, string $descricao): void
    {
        FasePedido::withoutEvents(function () use ($scope, $pedido, $item, $descricao): void {
            FasePedido::query()->create([
                'date_time' => now(),
                'idSituacao' => self::STATUS_EM_ANALISE,
                'Descricao' => $descricao,
                'Usuario' => $scope['id_egap'],
                'id_pedido' => $pedido->id,
                'id_itempedido' => $item?->id,
                'id_descricaoresumida' => $item?->material,
                'id_descricaodetalhada' => $item?->DescricaoDetalhada,
                'quantidade' => $item?->QuantidadeMaterial,
            ]);
        });
    }

    private function pedidoToArray(Pedidos $pedido): array
    {
        return [
            'id' => (int) $pedido->id,
            'data' => optional($pedido->date_time)->toIso8601String(),
            'tipo' => (int) $pedido->setor_responsavel === self::SETOR_ALMOXARIFADO ? 'consumo' : 'permanente',
            'situacao' => [
                'id' => $pedido->idSituacao,
                'descricao' => $pedido->situacao?->Descricao,
            ],
            'complemento_setor' => [
                'id' => $pedido->ComplementoSetor,
                'descricao' => $pedido->complementoSetor?->descricao,
            ],
            'observacao' => $pedido->Observacao,
            'itens' => $pedido->itens->map(fn (ItemPedido $item): array => [
                'id' => (int) $item->id,
                'material_id' => $item->material,
                'descricao_detalhada_id' => $item->DescricaoDetalhada,
                'material' => $item->material_nome,
                'imagem_url' => ImagemMaterial::url($item->descricaoDetalhadaRef?->imagem, $item->materialRef?->imagem),
                'quantidade' => (int) $item->QuantidadeMaterial,
                'quantidade_atendida' => (int) $item->QuantidadeMaterialAtendida,
                'situacao' => [
                    'id' => $item->situacao,
                    'descricao' => $item->situacaoRef?->Descricao,
                ],
                'justificativa' => $item->justificativa,
                'valor_material' => (float) ($item->valor_material ?? 0),
            ])->values()->all(),
        ];
    }

    private function formatarJustificativaPermanente(string $tipoAtendimento, string $justificativa, string $patrimonioSubstituido): string
    {
        if ($tipoAtendimento === 'substituicao') {
            return '{Substitui&ccedil;&atilde;o; Patrim&ocirc;nio:'.$patrimonioSubstituido.'; Justificativa:'.$justificativa.'}';
        }

        return '{Adi&ccedil;&atilde;o; Justificativa:'.$justificativa.'}';
    }

    /**
     * @return array<int>
     */
    private function visibilidadesPermitidas(int $unidadeJudiciaria): array
    {
        return VisibilidadeMaterial::permitidas($unidadeJudiciaria);
    }

    private function filledInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }
}
