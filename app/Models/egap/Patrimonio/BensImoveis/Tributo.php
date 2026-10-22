<?php

namespace App\Models\egap\Patrimonio\BensImoveis;

use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;

class Tributo extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_imo_tributos';
    protected $primaryKey = 'Id';
    protected $guarded = [];
    public $timestamps = false;

    protected $casts = [
        'eventos' => 'array',
    ];

    public function imovelRelacaoref()
    {
        return $this->belongsTo(BemImovel::class, 'Id_imovel', 'Id');
    }

    public function tipoTributoRelacaoref()
    {
        return $this->belongsTo(TipoTributo::class, 'tipo_tributo', 'id');
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
