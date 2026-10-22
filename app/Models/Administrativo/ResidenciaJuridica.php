<?php

namespace App\Models\Administrativo;

use App\Models\Escola\CursoCertificados;
use App\Models\Setores;
use App\Models\Traits\HasSetorScope;
use App\Models\Traits\RecordsUserDataAndSetor;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Auth;

class ResidenciaJuridica extends Model
{
    use HasSetorScope, RecordsUserDataAndSetor;
    protected $table = 'adm_residenciajuridica';
    protected $fillable = [
        'setor_id',
        'pessoa_id',
        'atualizado_por',
        'inicio',
        'termino',
        'municipio',
        'processo',
        'observacao',
        'carga_horaria',
        'curso',
        'observacao',
        'json',
        'situacao',
    ];
    protected function casts(): array
    {
        return [
            'situacao' => 'boolean',
            'curso' => 'boolean',
            'json' => 'array',
        ];
    }
    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'setor_id', 'id');
    }
    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pessoa_id', 'id');
    }
    public function certificados(): MorphToMany
    {
        return $this->morphedByMany(CursoCertificados::class, 'certificable');
    }
    protected static function booted(): void
    {
        parent::booted();
        static::bootRecordsUserDataAndSetor();
    }
}
