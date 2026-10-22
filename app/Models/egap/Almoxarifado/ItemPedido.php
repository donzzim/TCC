<?php

namespace App\Models\egap\Almoxarifado;

use App\Models\egap\Cadastro\DescricaoDetalhada;
use App\Models\egap\Cadastro\DescricaoResumida;
use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemPedido extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_ped_itempedido';
    public $timestamps = false;

    protected $fillable = [
        'date_time',
        'idPedido',
        'QuantidadeMaterial',
        'ObservacaoItem',
        'QuantidadeMaterialAtendida',
        'material',
        'DescricaoDetalhada',
        'data_validacao',
        'situacao',
        'justificativa',
        'validado_por',
        'data_cancelado',
        'cancelado_por',
        'quantidade_validada',
        'valor_material',
    ];

    protected $casts = [
        'date_time' => 'datetime',
        'data_validacao' => 'datetime',
        'data_cancelado' => 'datetime',
        'valor_material' => 'decimal:10',
        'QuantidadeMaterial' => 'integer',
        'quantidade_validada' => 'integer',
        'QuantidadeMaterialAtendida' => 'integer',
    ];

    public function idPedidoRef(): BelongsTo
    {
        return $this->belongsTo(Pedidos::class, 'idPedido', 'id');
    }

    public function materialRef(): BelongsTo
    {
        return $this->belongsTo(DescricaoResumida::class, 'material', 'id');
    }

    public function descricaoDetalhadaRef(): BelongsTo
    {
        return $this->belongsTo(DescricaoDetalhada::class, 'DescricaoDetalhada', 'id');
    }

    public function situacaoRef(): BelongsTo
    {
        return $this->belongsTo(SituacaoPedido::class, 'situacao', 'id');
    }

    public function validadoPorRef(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'validado_por', 'id');
    }

    public function canceladoPorRef(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'cancelado_por', 'id');
    }

    public function getJustificativaFormatadaAttribute(): ?string
    {
        return self::formatarJustificativa($this->justificativa);
    }

    // No banco ta com formato json, ai tem que formatar
    public static function formatarJustificativa(?string $justificativa): ?string
    {
        if (blank($justificativa)) {
            return $justificativa;
        }

        $decoded = html_entity_decode($justificativa, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (preg_match('/\{.*?Justificativa\s*:\s*(.*)\}\s*$/is', $decoded, $matches)) {
            return trim($matches[1]);
        }

        return trim($decoded);
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
        });
    }
}
