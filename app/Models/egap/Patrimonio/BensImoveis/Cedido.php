<?php

namespace App\Models\egap\Patrimonio\BensImoveis;

use App\Models\egap\UserEgap;
use App\Models\egap\User;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cedido extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_imo_cedidos';
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
    public $timestamps = false;

    protected $casts = [
        'despesas' => 'array',
    ];

    public function gestores()
    {
        return $this->hasMany(GestorCedido::class, 'id_cedidos', 'id');
    }

    public function imovelRelacaoref()
    {
        return $this->belongsTo(BemImovel::class, 'id_imovel', 'Id');
    }

    public function atualizadoPorAntigoRef() : BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'atualizado_por', 'id');
    }

    public function atualizadoPorNovoRef() : BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }

    public function tipoTributoRelacaoref()
    {
        return $this->belongsTo(TipoTributo::class, 'tipo_tributo', 'id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
            $model->atualizado_por = EgapSession::resolverAtual() ?? 111;
        });
    }
}
