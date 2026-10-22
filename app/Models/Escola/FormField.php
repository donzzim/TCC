<?php

namespace App\Models\Escola;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormField extends Model
{
    protected $table = 'esc_formfields';
    protected $fillable = [
        'form_id',
        'label',
        'type',
        'required',
        'options',
        'order',
    ];

    protected $casts = [
        'required' => 'boolean',
        'options' => 'array',
    ];
    public function form(): BelongsTo
    {
        return $this->belongsTo(FormAvaliacao::class, 'form_id', 'id');
    }
    // protected static function booted(): void
    // {
    //     static::creating(function ($form) {
    //         $form->options = json_encode($form->options);
    //     });
    //     static::updating(function ($form) {
    //         $form->options = json_encode($form->options);
    //     });
    // }
}
