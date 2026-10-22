<?php

namespace App\Models\egap\Patrimonio\BensImoveis;

use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TermoResponsabilidade extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_imo_termos';
    protected $primaryKey = 'id';
    protected $guarded = [];
    public $timestamps = false;

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
