<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CursosEmeron extends Model
{
    protected $table = 'rel_cursos_emeron';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'ano',
        'titulo',
        'carga_horaria',
        'vagas',
        'data_inicio',
        'data_termino',
        'textoSupNome',
        'textoInfNome',
        'textoComplemento',
        'flDisponivel',
        'cpf',
        'nome',
    ];
}
