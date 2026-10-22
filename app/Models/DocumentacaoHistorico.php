<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentacaoHistorico extends Model
{
    protected $table = 'documentacao_historicos';
    protected $fillable = [
        'documentacao_id',
        'user_id',
        'conteudo_anterior',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
