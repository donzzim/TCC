<?php

namespace App\Models\Portal;

use App\Models\Setores;
use App\Models\Traits\HasSetorScope;
use App\Models\Traits\RecordsUserDataAndSetor;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CMSTemplates extends Model
{
    use HasSetorScope, RecordsUserDataAndSetor;

    protected $table = 'cms_templates';
    protected $fillable = [
        'setor_id',
        'atualizado_por',
        'name',
        'view',
        'css_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
    public static function getActive()
    {
        return self::where('is_active', true)->first();
    }
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
