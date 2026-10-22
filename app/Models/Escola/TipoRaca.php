<?php

namespace App\Models\Escola;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TipoRaca extends Model
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

    protected static function booted(): void
    {
        static::addGlobalScope('tipopublico', function (Builder $builder) {
            $builder->where('cad_tabelaapoio.grupo', '=', 'RACA_COR');
        });
        static::creating(function (TipoRaca $row) {
            $row->grupo = 'RACA_COR';
        });
    }
}
