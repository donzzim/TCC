<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class Lotacao extends Pivot
{
    protected $table = 'user_lotacao';
    public $incrementing = true;
    public $primarykey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'user_id',
        'setor_id',
        'atualizado_por',
        'principal',
    ];
    protected $casts = [
        'principal' => 'boolean',
    ];
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public static function usuariosSetorPrincipal()
    {
        $lista = Lotacao::where('setor_id', '=', Session::get('setorprincipal'))->get();
        $return = [];
        foreach ($lista as $l) {
            $return[$l->usuario->id] = $l->usuario->name;
        }
        return $return;
    }
    public function atualizado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }
    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setores::class, 'setor_id', 'id');
    }
    protected static function booted(): void
    {
        static::creating(function (Lotacao $row) {
            if (!$row->atualizado_por) {
                $row->atualizado_por = Auth::user()->id;
            }
        });

        static::updating(function (Lotacao $row) {
            if (!$row->atualizado_por) {
                $row->atualizado_por = Auth::user()->id;
            }
        });
    }
}
