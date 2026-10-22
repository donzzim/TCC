<?php

namespace App\Models\egap\Cadastro;

use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Marcas extends Model
{
    //
    protected $connection = 'emes';
    protected $table = 'egap_mat_marca';
    protected $fillable = [
        'date_time',
        'Usuario',
        'descricao',
        'tipobem',
    ];

    public function modelos(): HasMany
    {
        return $this->HasMany(Modelos::class, 'marca', 'id');
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
