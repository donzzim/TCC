<?php

namespace App\Models\Escola;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoEvento extends Model
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
        return $this->hasMany(Cursos::class, 'tipoevento_id', 'id');
    }
    protected static function booted(): void
    {
        static::addGlobalScope('tipoevento', function (Builder $builder) {
            $builder->where('cad_tabelaapoio.grupo', '=', 'TIPO_EVENTO');
        });
        static::creating(function (TipoEvento $row) {
            $row->grupo = 'TIPO_EVENTO';
        });
    }
}
