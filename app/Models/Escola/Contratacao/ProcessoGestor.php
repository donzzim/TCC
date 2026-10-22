<?php

namespace App\Models\Contratacao;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProcessoGestor extends Model
{
    protected $table = 'cnt_gestores';
    protected $fillable = [
        'processo_id',
        'gestor_id',
        'principal',
    ];
    protected function casts(): array
    {
        return [
            'principal' => 'boolean',
        ];
    }

    public function gestores(): BelongsToMany
    {
        return $this->belongsToMany(Processo::class, 'cnt_gestores', 'gestor_id', 'processo_id');
    }
}
