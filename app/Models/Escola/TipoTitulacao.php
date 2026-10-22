<?php

namespace App\Models\Escola;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** @property string|null $descricao */
class TipoTitulacao extends Model
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
            $builder->where('cad_tabelaapoio.grupo', '=', 'TIPO_TITULACAO');
        });
        static::creating(function (TipoTitulacao $row) {
            $row->grupo = 'TIPO_TITULACAO';
        });
    }
}
