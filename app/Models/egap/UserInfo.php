<?php

namespace App\Models\egap;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserInfo extends Model
{
    protected $connection = 'emes';

    protected $table = 'user_perfil';

    // Campos de `user_perfil` editados pelo EGAP (cargo + formulário de
    // Usuários EGAP). Os demais são do EMES e ficam de fora.
    protected $fillable = [
        'user_id',
        'cargo',
        'tipopessoa_id',
        'categoria_profissional',
        'pis_pasep_nit',
        'contato',
        'setor_orgao',
        'raca',
        'sexo',
        'genero',
        'chefia',
        'estrangeiro',
        'pcd_necessidade',
        'curriculo',
    ];

    protected $casts = [
        'chefia' => 'boolean',
        'estrangeiro' => 'boolean',
        'pcd_necessidade' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
