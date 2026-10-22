<?php

namespace App\Models\Escola;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

/** @property string|null $slug */
class FormAvaliacao extends Model
{
    protected $table = 'esc_formavaliacao';

    protected $fillable = [
        'name',
        'slug',
        'auth_required',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class, 'form_id', 'id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'form_id', 'id');
    }

    protected static function booted(): void
    {
        static::creating(function ($form) {
            $form->slug = Str::slug(Uuid::uuid4());
        });
        // static::updating(function ($form) {
        //     $form->slug = Str::slug( Uuid::uuid4());
        // });
    }
}
