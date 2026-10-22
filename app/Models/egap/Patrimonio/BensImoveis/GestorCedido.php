<?php

namespace App\Models\egap\Patrimonio\BensImoveis;

use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;

class GestorCedido extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_imo_gestorcedidos';
    protected $primaryKey = 'id';
    protected $guarded = [];
    public $timestamps = false;

    public function nomeRelacaoref()
    {
        return $this->belongsTo(UserEgap::class, 'nome', 'id');
    }

    public function atualizadoPorRelacaoref()
    {
        return $this->belongsTo(UserEgap::class, 'atualizado_por', 'id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
            $model->atualizado_por = EgapSession::resolverAtual() ?? 111;
        });
    }
}
