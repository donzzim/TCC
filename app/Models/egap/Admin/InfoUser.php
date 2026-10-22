<?php

namespace App\Models\egap\Admin;

use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfoUser extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_mat_infousers';

    public $timestamps = false;

    protected $fillable = [
        'date_time',
        'usuario_id',
        'cpf',
        'matricula',
        'cargo',
    ];

    protected $casts = [
        'date_time' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'usuario_id', 'id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
        });
    }
}
