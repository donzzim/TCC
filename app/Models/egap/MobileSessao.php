<?php

namespace App\Models\egap;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\PersonalAccessToken;

class MobileSessao extends Model
{
    protected $connection = 'emes';

    protected $table = 'egap_mobile_sessoes';

    protected $fillable = [
        'token_id',
        'user_id',
        'egap_id',
        'setor_id',
        'codigodauo',
        'info',
        'dispositivo',
    ];

    protected function casts(): array
    {
        return ['info' => 'array'];
    }

    public function token(): BelongsTo
    {
        return $this->belongsTo(PersonalAccessToken::class, 'token_id');
    }
}
