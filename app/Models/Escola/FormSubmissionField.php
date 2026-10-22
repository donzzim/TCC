<?php

namespace App\Models\Escola;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSubmissionField extends Model
{
    protected $table = 'esc_formsubmissionfields';
    protected $fillable = [
        'form_submission_id',
        'form_field_id',
        'value',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'form_submission_id', 'id');
    }
    public function field(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'form_field_id', 'id');
    }
}
