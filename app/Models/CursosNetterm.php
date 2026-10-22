<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CursosNetterm extends Model
{
    protected $table = 'vw_cursos_netterm';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'numero_curso',
        'titulo',
        'data_inicio',
        'data_termino',
        'instrutor',
        'local',
        'qtde_horas',
        'qtde_vagas',
        'nota_aluno',
        'perc_frequencia',
        'cpf',
        'matricula',
        'nome',
        'mag_serv',
    ];
}
