<?php

namespace App\Models\Escola;

use App\Models\Escola\Cursos;
use App\Models\Escola\TipoRecursos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AreaTematica extends Model
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
        return $this->hasMany(Cursos::class, 'areatematica_id', 'id');
    }
    protected static function booted(): void
    {
        static::addGlobalScope('tipopublico', function (Builder $builder) {
            $builder->where('grupo', '=', 'AREA_TEMATICA');
        });
        static::creating(function (AreaTematica $row) {
            $row->grupo = 'AREA_TEMATICA';
        });
    }
}
