<?php

namespace App\Models\egap\Almoxarifado;

use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TipoDocumento extends Model
{
    //
    protected $connection = 'emes';
    public $timestamps = false;
    protected $table = 'egap_alm_tipo_documento';
    protected $fillable = [
        'date_time',
        'descricao',
        'atualizado_por',
    ];

    public function atualizadoPor(): BelongsTo
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
