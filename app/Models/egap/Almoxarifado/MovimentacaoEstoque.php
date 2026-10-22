<?php

namespace App\Models\egap\Almoxarifado;

use App\Models\egap\Cadastro\DescricaoDetalhada;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class MovimentacaoEstoque extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_alm_estoque';

    public $timestamps = false;

    protected $fillable = [
        'date_time',
        'tipo_movimentacao',
        'nota_fiscal',
        'material',
        'quantidade',
        'preco_unitario',
        'atualizado_por',
        'valor_total',
        'quantidade_estoque',
        'preco_medio_estoque',
        'valor_total_estoque',
        'id_setor',
        'id_pedido',
    ];

    protected $casts = [
        'date_time' => 'datetime',
        'quantidade' => 'integer',
        'preco_unitario' => 'decimal:2',
        'valor_total' => 'decimal:2',
        'quantidade_estoque' => 'integer',
        'preco_medio_estoque' => 'decimal:2',
        'valor_total_estoque' => 'decimal:2',
    ];

    public function atualizadoPor(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'atualizado_por', 'id');
    }

    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'id_setor', 'id');
    }

    public function notaFiscal(): BelongsTo
    {
        return $this->belongsTo(NotaFiscal::class, 'nota_fiscal', 'id');
    }

    public function materialRel(): BelongsTo
    {
        return $this->belongsTo(DescricaoDetalhada::class, 'material', 'id');
    }

    public function idPedidoRef(): BelongsTo
    {
        return $this->belongsTo(Pedidos::class, 'id_pedido', 'id');
    }

    public function tipoMovimentacaoRel(): BelongsTo
    {
        return $this->belongsTo(TipoMovimentacaoNotaFiscal::class, 'tipo_movimentacao', 'id');
    }

    public static function estoquesAtuais(array $materialIds): Collection
    {
        if ($materialIds === []) {
            return collect();
        }

        return static::query()
            ->whereIn('id', static::query()
                ->selectRaw('MAX(id)')
                ->whereIn('material', $materialIds)
                ->groupBy('material'))
            ->get()
            ->keyBy('material');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
            $model->atualizado_por = EgapSession::resolverAtual() ?? 111;

            $quantidade = (float) ($model->quantidade ?? 0);
            $precoUnitario = (float) ($model->preco_unitario ?? 0);

            if (empty($model->valor_total) && $quantidade > 0 && $precoUnitario > 0) {
                $model->valor_total = $quantidade * $precoUnitario;
            }
        });
    }
}
