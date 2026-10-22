<?php

namespace App\Models\Portal;

use App\Models\Setores;
use App\Models\Traits\HasSetorScope;
use App\Models\Traits\RecordsUserDataAndSetor;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CMSSections extends Model
{
    use HasSetorScope, RecordsUserDataAndSetor;

    protected $table = 'cms_sections';
    protected $fillable = [
        'setor_id',
        'atualizado_por',
        'type',
        'content',
    ];
    protected $casts = [
        'content' => 'array',
    ];
    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'setor_id', 'id');
    }
    public function atualizado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }
    protected static function booted()
    {
        parent::booted();
        static::bootRecordsUserDataAndSetor();
    }
}
