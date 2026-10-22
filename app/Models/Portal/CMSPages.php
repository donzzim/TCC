<?php

namespace App\Models\Portal;

use App\Models\Setores;
use App\Models\Traits\HasSetorScope;
use App\Models\Traits\RecordsUserDataAndSetor;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CMSPages extends Model
{
    use HasSetorScope, RecordsUserDataAndSetor;

    protected $table = 'cms_pages';
    protected $fillable = [
        'setor_id',
        'atualizado_por',
        'title',
        'slug',
        'template',
        'content',
        'seo_title',
        'seo_description',
        'seo_keywords',
    ];
    protected $casts = [
        'seo_keywords' => 'array',
    ];
    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'setor_id', 'id');
    }
    public function atualizado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }

    public function articles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CMSPageArticles::class, 'cms_page_id', 'id')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderBy('sort_order')
            ->orderBy('title');
    }
    protected static function booted()
    {
        parent::booted();
        static::bootRecordsUserDataAndSetor();

        static::creating(function ($row) {
            $slug = Str::slug($row->title);
            $originalSlug = $slug;
            $count = 1;

            while (static::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }

            $row->slug = $slug;
        });
    }
}
