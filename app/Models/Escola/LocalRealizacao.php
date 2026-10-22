<?php

namespace App\Models\Escola;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LocalRealizacao extends Model
{
    protected $table = 'cad_tabelaapoio';
    protected $fillable = [
        'grupo',
        'codigo',
        'descricao',
        'json',
    ];
    protected $casts = [
        'json' => 'array',
    ];

    public function cursos(): HasMany
    {
        return $this->hasMany(Cursos::class, 'realizado_id', 'id');
    }
    public function capacidade(): int
    {
        return $this->json->capacidade;
    }
    protected static function booted(): void
    {
        static::addGlobalScope('localrealizacao', function (Builder $builder) {
            $builder->where('grupo', '=', 'LOCAL_REALIZACAO');
        });
        static::creating(function (LocalRealizacao $row) {
            $row->grupo = 'LOCAL_REALIZACAO';
        });
    }
}
