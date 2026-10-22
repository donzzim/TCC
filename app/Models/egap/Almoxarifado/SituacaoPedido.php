<?php

namespace App\Models\egap\Almoxarifado;

use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SituacaoPedido extends Model
{
    protected $connection = 'emes';
    public $timestamps = false;
    protected $table = 'egap_ped_situacao';
    protected $fillable = [
        'date_time',
        'Descricao',
        'Usuario',
        'modulo'
    ];

    public function atualizadoPor(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'Usuario', 'id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
            $model->Usuario = EgapSession::resolverAtual() ?? 111;
        });
    }
}
