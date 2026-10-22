<?php

namespace App\Models\Portal;

use App\Models\Setores;
use App\Models\Traits\HasSetorScope;
use App\Models\Traits\RecordsUserDataAndSetor;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CMSPageArticles extends Model
{
    use HasSetorScope, RecordsUserDataAndSetor;

    protected $table = 'cms_page_articles';

    protected $fillable = [
        'cms_page_id',
        'setor_id',
        'atualizado_por',
        'title',
        'author',
        'publication_type',
        'issn',
        'doi',
        'volume',
        'month',
        'year',
        'pages',
        'pdf_path',
        'is_published',
        'sort_order',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(CMSPages::class, 'cms_page_id', 'id');
    }

    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'setor_id', 'id');
    }

    public function atualizado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where(function (Builder $publishedQuery): void {
                $publishedQuery
                    ->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function getMonthNameAttribute(): ?string
    {
        return match ((int) $this->month) {
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro',
            default => null,
        };
    }

    public function getPublicationTypeLabelAttribute(): string
    {
        return match ($this->publication_type) {
            'publicacao' => 'Publicação',
            'artigo' => 'Artigo',
            'monografia' => 'Monografia',
            'revista' => 'Revista',
            'outro' => 'Outro',
            default => 'Publicação',
        };
    }

    public function getPdfUrlAttribute(): ?string
    {
        if (blank($this->pdf_path)) {
            return null;
        }

        if (str_starts_with($this->pdf_path, 'http://') || str_starts_with($this->pdf_path, 'https://')) {
            return $this->pdf_path;
        }

        $path = str_replace('\\', '/', $this->pdf_path);

        if (Storage::disk('revista')->exists($path)) {
            return Storage::disk('revista')->url($path);
        }

        $fileName = basename($path);

        if ($fileName !== $path && Storage::disk('revista')->exists($fileName)) {
            return Storage::disk('revista')->url($fileName);
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        return Storage::disk('revista')->url($fileName);
    }

    public function getDoiUrlAttribute(): ?string
    {
        if (blank($this->doi)) {
            return null;
        }

        $doi = trim($this->doi);

        if (str_starts_with($doi, 'http://') || str_starts_with($doi, 'https://')) {
            return $doi;
        }

        $doi = preg_replace('/^doi:\s*/i', '', $doi);
        $doi = preg_replace('#^doi\.org/#i', '', $doi);

        return 'https://doi.org/' . $doi;
    }
}
