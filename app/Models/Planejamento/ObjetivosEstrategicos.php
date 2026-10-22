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

class ObjetivosEstrategicos extends Model
{
    use HasSetorScope, RecordsUserDataAndSetor;

    protected $table = 'pla_objetivos';
    protected $fillable = [
        'planejamento_id',
        'perspectiva_id',
        'tema_id',
        'macrodesafio_id',
        'setor_id',
        'atualizado_por',
        'objetivo',
        'texo_complementar',
    ];
    public function planejamento(): BelongsTo
    {
        return $this->belongsTo(PlanejamentoEstrategico::class, 'planejamento_id', 'id');
    }
    public function perspectiva(): BelongsTo
    {
        return $this->belongsTo(PerspectivasOrganizacionais::class, 'perspectiva_id', 'id');
    }
    public function tema(): BelongsTo
    {
        return $this->belongsTo(TemasEstrategicos::class, 'tema_id', 'id');
    }
    public function macrodesafio(): BelongsTo
    {
        return $this->belongsTo(MacrodesafiosEstrategicos::class, 'macrodesafio_id', 'id');
    }
    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'setor_id', 'id');
    }
    public function atualizado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }
    public function iniciativas(): HasMany
    {
        return $this->HasMany(IniciativasEstrategicas::class, 'objetivo_id', 'id');
    }
    protected static function booted(): void
    {
        parent::booted();
        static::bootRecordsUserDataAndSetor();
    }
}
