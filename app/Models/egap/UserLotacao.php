<?php

namespace App\Models\egap;

use App\Models\egap\Cadastro\Setores;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLotacao extends Model
{
    protected $connection = 'emes';

    protected $table = 'user_lotacao';

    protected $fillable = [
        'user_id',
        'setor_id',
        'atualizado_por',
        'principal',
    ];

    protected $casts = [
        'principal' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $lotacao): void {
            if (blank($lotacao->atualizado_por)) {
                $lotacao->atualizado_por = auth(User::GUARD)->id();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'setor_id', 'id');
    }

    public function atualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }

    public function scopeDoUsuario(Builder $query, int $userId): Builder
    {
        return $query
            ->where('user_lotacao.user_id', $userId)
            ->join('setores', 'setores.id', '=', 'user_lotacao.setor_id')
            ->orderByDesc('user_lotacao.principal')
            ->orderBy('setores.setor')
            ->select('user_lotacao.*');
    }
}
