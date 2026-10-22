<?php

namespace App\Models\Portal;

use App\Models\Setores;
use App\Models\Traits\HasSetorScope;
use App\Models\Traits\RecordsUserDataAndSetor;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CMSMenuItems extends Model
{
    use HasSetorScope, RecordsUserDataAndSetor;

    protected $table = 'cms_menuitems';
    protected $fillable = [
        'setor_id',
        'atualizado_por',
        'menu_id',
        'parent_id',
        'label',
        'url',
        'order',
        'open_in_new_tab',
    ];
    protected $casts = [
        'order' => 'integer',
        'open_in_new_tab' => 'boolean',
    ];

    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'setor_id', 'id');
    }
    public function atualizado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }
    public function menu()
    {
        return $this->belongsTo(CMSMenus::class);
    }
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('order');
    }
    protected static function booted()
    {
        parent::booted();
        static::bootRecordsUserDataAndSetor();
    }
}
