<?php

namespace App\Models\egap\Cadastro;

use App\Models\egap\Patrimonio\BensImoveis\BemImovel;
use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContaContabil extends Model
{
    //
    protected $connection = 'emes';
    protected $table = 'egap_mat_planocontas';
    public $timestamps = false;
    protected $fillable = [
        'date_time',
        'codigo',
        'titulo',
        'funcao',
        'vinculo',
        'usuario'
    ];

    public function atualizado_por(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'usuario', 'id');
    }

    public function bensImoveis(): HasMany
    {
        return $this->hasMany(BemImovel::class, 'id_planocontas', 'id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
            $model->usuario = EgapSession::resolverAtual() ?? 111;
        });
    }
}
