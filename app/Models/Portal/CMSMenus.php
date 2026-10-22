<?php

namespace App\Models\Portal;

use App\Models\Setores;
use App\Models\Traits\HasSetorScope;
use App\Models\Traits\RecordsUserDataAndSetor;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CMSMenus extends Model
{
    use HasSetorScope, RecordsUserDataAndSetor;

    protected $table = 'cms_menus';
    protected $fillable = [
        'setor_id',
        'atualizado_por',
        'name',
    ];
    public function items()
    {
        return $this->hasMany(CMSMenuItems::class, 'menu_id')->orderBy('order');
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
