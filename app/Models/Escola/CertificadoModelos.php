<?php

namespace App\Models\Escola;

use App\Models\Escola\CursoCertificados;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class CertificadoModelos extends Model
{
    protected $table = 'esc_certificadomodelos';
    protected $fillable = [
        'atualizado_por',
        'assinado_por',
        'titulo',
        'arquivo',
        'texto',
    ];
    public function atualizado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }
    public function assinado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assinado_por', 'id');
    }
    public function certificados(): HasMany
    {
        return $this->hasMany(CursoCertificados::class, 'modelo_id', 'id');
    }
    protected static function booted(): void
    {
        static::creating(function (CertificadoModelos $row) {
            $row->atualizado_por = Auth::user()->id;
        });

        static::updating(function (CertificadoModelos $row) {
            $row->atualizado_por = Auth::user()->id;
        });
    }
}
