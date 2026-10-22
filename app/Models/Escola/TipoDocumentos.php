<?php

namespace App\Models\Escola;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TipoDocumentos extends Model
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
    public function documentos()
    {
        return $this->hasMany(Documentos::class, 'tipodocumento_id', 'id');
    }
    protected static function booted(): void
    {
        static::addGlobalScope('tiporecurso', function (Builder $builder) {
            $builder->where('grupo', '=', 'TIPO_DOCUMENTOS');
        });

        static::creating(function (TipoDocumentos $row) {
            $row->grupo = 'TIPO_DOCUMENTOS';
        });
    }
}
