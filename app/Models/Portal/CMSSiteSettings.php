<?php

namespace App\Models\Portal;

use App\Models\Setores;
use App\Models\Traits\HasSetorScope;
use App\Models\Traits\RecordsUserDataAndSetor;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CMSSiteSettings extends Model
{
    use HasSetorScope, RecordsUserDataAndSetor;

    protected $table = 'cms_sitesettings';
    protected $fillable = [
        'setor_id',
        'atualizado_por',
        'name',
        'brand_name',
        'favicon_path',
        'logo_path',
        'social_links',
        'phone',
        'email',
        'address',
        'maps_iframe',
        'primary_color',
        'secondary_color',
        'tertiary_color',
    ];
    protected $casts = [
        'social_links' => 'array',
    ];
    // public function getLogoUrlAttribute(): ?string
    // {
    //     return $this->logo_path
    //         ? Storage::disk('public')->url($this->logo_path)
    //         : null;
    // }
    public static function getSettings(): ?self
    {
        return self::first();
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
