<?php

namespace App\Models\egap\Patrimonio\BensImoveis;

use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntradaSaida extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_imo_entradasaida';
    protected $guarded = ['id'];
    public $timestamps = false;

    public function usuarioRef(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'usuario', 'id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
            $model->usuario = EgapSession::resolverAtual() ?? 111;
        });
    }
}
