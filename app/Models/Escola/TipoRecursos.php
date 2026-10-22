<?php

namespace App\Models\Escola;

use App\Models\Escola\Cursos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TipoRecursos extends Model
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
    public function cursos(): BelongsToMany
    {
        return $this->belongsToMany(Cursos::class, 'esc_recursos', 'tiporecurso_id', 'curso_id');
    }
    protected static function booted(): void
    {
        static::addGlobalScope('tiporecurso', function (Builder $builder) {
            $builder->where('cad_tabelaapoio.grupo', '=', 'TIPO_RECURSOS');
        });
        static::creating(function (TipoRecursos $row) {
            $row->grupo = 'TIPO_RECURSOS';
        });
    }
}
