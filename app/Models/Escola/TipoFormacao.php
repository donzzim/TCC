<?php

namespace App\Models\Escola;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoFormacao extends Model
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
        return $this->hasMany(Cursos::class, 'tipoformacao_id', 'id');
    }
    protected static function booted(): void
    {
        static::addGlobalScope('tipoformacao', function (Builder $builder) {
            $builder->where('grupo', '=', 'TIPO_FORMACAO');
        });
        static::creating(function (TipoFormacao $row) {
            $row->grupo = 'TIPO_FORMACAO';
        });
    }
}
