<?php

namespace App\Models\Planejamento;

use App\Models\Setores;
use App\Models\Traits\HasSetorScope;
use App\Models\Traits\RecordsUserDataAndSetor;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class IniciativasEstrategicas extends Model
{
    use HasSetorScope, RecordsUserDataAndSetor;

    protected $table = 'pla_iniciativas';
    protected $fillable = [
        'objetivo_id',
        'setor_id',
        'atualizado_por',
        'codigo',
        'iniciativa',
    ];
    public function objetivo(): BelongsTo
    {
        return $this->belongsTo(ObjetivosEstrategicos::class, 'objetivo_id', 'id');
    }
    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'setor_id', 'id');
    }
    public function atualizado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }
    protected static function booted(): void
    {
        parent::booted();
        static::bootRecordsUserDataAndSetor();
    }
}
