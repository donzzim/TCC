<?php

namespace App\Models\egap\Patrimonio\BensIntangiveis;

use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Fabricante extends Model
{
    //
    protected $connection = 'emes';
    public $timestamps = false;
    protected $table = 'egap_int_fabricante';
    protected $fillable = [
        'descricao',
        'atualizado_por',
        'date_time',
    ];

    public function atualizadoPorRef(): BelongsTo
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
