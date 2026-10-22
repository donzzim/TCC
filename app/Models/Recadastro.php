<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Recadastro extends Model
{

    /*
    **
    create or replace view vw_recadastro as
    select distinct r.no_cpf_pessoa as 'id', r.no_cpf_pessoa as 'cpf', r.nm_pessoa as 'nome', r.email_pessoa_trab as 'email',
        concat(r.no_telefo_celular, r.no_telefo_celular) as 'telefone_celular'
    from recadastro r
    where r.ano_ref = year(now())
    union all
    select distinct c.pk_cpf, c.pk_cpf, c.nome_completo, null, null
    from recadastro_dadosanexoscnj c
    where c.ano = year(now());
    */


    protected $table = 'vw_recadastro';
    protected $primaryKey = 'cpf';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $fillable = [
        'cpf',
        'nome',
        'telefonecelular',
        'email',
        'email_pessoal',
        'telefone_celular',
        'centrocusto',
        'tipovinc',
        'cargo',
        'funcao',
        'cd_orgao',
        'nomesetor',
    ];
    public function usuario(): HasOne
    {
        return $this->hasOne(User::class, 'cpf', 'cpf');
    }
}
