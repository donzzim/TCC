<?php

namespace App\Models\Planejamento;

use App\Models\Setores;
use App\Models\Traits\HasSetorScope;
use App\Models\Traits\RecordsUserDataAndSetor;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class PlanejamentoEstrategico extends Model
{
    use HasSetorScope, RecordsUserDataAndSetor;

    protected $table = 'pla_estrategico';
    protected $fillable = [
        'setor_id',
        'atualizado_por',
        'periodo',
        'missao',
        'visao',
        'valores',
    ];
    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'setor_id', 'id');
    }
    public function atualizado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }
    public function objetivos(): HasMany
    {
        return $this->hasMany(ObjetivosEstrategicos::class, 'planejamento_id');
    }
    protected static function booted(): void
    {
        parent::booted();
        static::bootRecordsUserDataAndSetor();
    }
}
