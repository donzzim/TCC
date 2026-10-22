<?php

namespace App\Models\Escola;

use App\Models\Perfil;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoPublico extends Model
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
        return $this->belongsToMany(Cursos::class, 'curso_publico', 'tipopublico_id', 'curso_id');
    }
    public function perfis(): HasMany
    {
        return $this->hasMany(Perfil::class, 'tipopessoa_id', 'id');
    }
    protected static function booted(): void
    {
        static::addGlobalScope('tipopublico', function (Builder $builder) {
            $builder->where('cad_tabelaapoio.grupo', '=', 'PUBLICO_ALVO');
        });
        static::creating(function (TipoPublico $row) {
            $row->grupo = 'PUBLICO_ALVO';
        });
    }
}
