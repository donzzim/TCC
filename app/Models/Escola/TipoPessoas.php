<?php

namespace App\Models\Escola;

use App\Models\Escola\Cursos;
use App\Models\Perfil;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TipoPessoas extends Model
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
    // public function perfil(): BelongsTo
    // {
    //     return $this->belongsTo(Perfil::class, 'tipopessoa_id', 'id');
    // }
    protected static function booted(): void
    {
        static::addGlobalScope('tipopessoa', function (Builder $builder) {
            $builder->where('grupo', '=', 'TIPO_PESSOA');
        });
        static::creating(function (TipoRecursos $row) {
            $row->grupo = 'TIPO_PESSOA';
        });
    }
}
