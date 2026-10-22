<?php

namespace App\Models\egap\Cadastro;

use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElementoDespesa extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_mat_produtos';

    protected $fillable = [
        'CodigodaClasse',
        'DescricaodaClasse',
        'Despesa',
        'VidaUtil',
        'date_time',
        'Usuario',
        'ValorResidual',
        'item_patrimonial',
    ];

    public function atualizado_por(): BelongsTo
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
