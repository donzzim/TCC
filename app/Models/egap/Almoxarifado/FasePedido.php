<?php

namespace App\Models\egap\Almoxarifado;

use App\Models\egap\Cadastro\DescricaoDetalhada;
use App\Models\egap\Cadastro\DescricaoResumida;
use App\Models\egap\Patrimonio\BensMoveis\Termo;
use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class FasePedido extends Model
{
    //
    protected $connection = 'emes';
    public $timestamps = false;
    protected $table = 'egap_ped_fases';
    protected $fillable = [
        'date_time',
        'idSituacao',
        'Descricao',
        'Usuario',
        'id_pedido',
        'id_itempedido',
        'id_descricaoresumida',
        'id_descricaodetalhada',
        'quantidade',
        'id_termo',
    ];

    protected $casts = [
        'date_time' => 'datetime',
        'quantidade' => 'integer',
    ];

    public function situacaoRef(): BelongsTo
    {
        return $this->belongsTo(SituacaoPedido::class, 'idSituacao', 'id');
    }

    public function usuarioRef(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'Usuario', 'id');
    }

    public function pedidoRef(): BelongsTo
    {
        return $this->belongsTo(Pedidos::class, 'id_pedido', 'id');
    }

    public function itemPedidoRef(): BelongsTo
    {
        return $this->belongsTo(ItemPedido::class, 'id_itempedido', 'id');
    }

    public function descricaoResumidaRef(): BelongsTo
    {
        return $this->belongsTo(DescricaoResumida::class, 'id_descricaoresumida', 'id');
    }

    public function descricaoDetalhadaRef(): BelongsTo
    {
        return $this->belongsTo(DescricaoDetalhada::class, 'id_descricaodetalhada', 'id');
    }

    public function termoRef(): BelongsTo
    {
        return $this->belongsTo(Termo::class, 'id_termo', 'id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
            $model->Usuario = EgapSession::resolverAtual() ?? 111;
        });
    }
}
