<?php

namespace App\Models\egap\Cadastro;

use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Modelos extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_mat_modelo';
    protected $fillable = [
        'date_time',
        'Usuario',
        'marca',
        'descricao',
    ];

    public function marca_ref(): BelongsTo
    {
        return $this->belongsTo(Marcas::class, 'marca', 'id');
    }

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
