<?php

namespace App\Models\egap\Agendamento;

use App\Models\egap\Almoxarifado\Pedidos;
use App\Models\egap\Patrimonio\BensMoveis\Termo;
use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Materiais extends Model
{
    public const TIPO_TRANSPORTE_CARGA = '2';

    protected $connection = 'emes';
    protected $table = 'egap_age_materiais';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'date_time',
        'id_user',
        'id_pedido',
        'id_termo',
        'id_solicitacao',
        'data_recebimento',
        'data_entrega',
    ];

    public function scopeTipoTransporteCarga(Builder $query): Builder
    {
        return $query->whereHas('idSolicitacaoRef', function (Builder $solicitacaoQuery): void {
            $solicitacaoQuery->where('tipo', self::TIPO_TRANSPORTE_CARGA);
        });
    }

    public function idSolicitacaoRef(): BelongsTo
    {
        return $this->belongsTo(Solicitacao::class, 'id_solicitacao', 'id');
    }

    public function idPedidoRef(): BelongsTo
    {
        return $this->belongsTo(Pedidos::class, 'id_pedido', 'id');
    }

    public function idTermoRef(): BelongsTo
    {
        return $this->belongsTo(Termo::class, 'id_termo', 'id');
    }

    public function idUserRef(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'id_user', 'id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
            $model->id_user = EgapSession::resolverAtual() ?? 111;
        });
    }
}
