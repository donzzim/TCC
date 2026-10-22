<?php

namespace App\Models\egap\Cadastro;

use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplementoSetor extends Model
{
    protected $connection = 'emes';
    public $timestamps = false;
    protected $table = 'egap_mat_complementosetor';
    protected $fillable = [
        'date_time',
        'descricao',
        'Usuario',
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
