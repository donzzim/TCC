<?php

namespace App\Models\Escola;

use App\Models\Escola\TipoRecursos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Modalidade extends Model
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
        return $this->hasMany(Cursos::class, 'modalidade_id', 'id');
    }
    protected static function booted(): void
    {
        static::addGlobalScope('modalidade', function (Builder $builder) {
            $builder->where('cad_tabelaapoio.grupo', '=', 'MODALIDADE');
        });
        static::creating(function (Modalidade $row) {
            $row->grupo = 'MODALIDADE';
        });
    }
}
