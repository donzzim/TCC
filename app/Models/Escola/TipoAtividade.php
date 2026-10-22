<?php

namespace App\Models\Escola;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoAtividade extends Model
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
        return $this->hasMany(Cursos::class, 'tipoatividade_id', 'id');
    }
    protected static function booted(): void
    {
        static::addGlobalScope('tipoatividade', function (Builder $builder) {
            $builder->where('cad_tabelaapoio.grupo', '=', 'TIPO_ATIVIDADE');
        });
        static::creating(function (TipoAtividade $row) {
            $row->grupo = 'TIPO_ATIVIDADE';
        });
    }
}
