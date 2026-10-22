<?php

namespace App\Models\Escola;

use App\Models\Cadastro\NaturezaDespesa;
use App\Models\Cadastro\Tabelas;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class AcaoDespesas extends Model
{
    protected $table = 'esc_acaodespesas';
    protected $fillable = [
        'acao_id',
        'natureza_id',
        'fonte_id',
        'instrutor_id',
        'situacao_id',
        'atualizado_por',
        'ano',
        'descricao',
        'tipo',
        'horas_aula',
        'quantidade',
        'valor',
        'observacao',
    ];

    public function acao(): BelongsTo
    {
        return $this->belongsTo(Acao::class, 'acao_id', 'id');
    }
    public function atualizado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }
    public function natureza(): BelongsTo
    {
        return $this->belongsTo(NaturezaDespesa::class, 'natureza_id', 'id');
    }
    public function situacao(): BelongsTo
    {
        return $this->belongsTo(Tabelas::class, 'situacao_id', 'id');
    }
    public function instrutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instrutor_id', 'id');
    }
    protected static function booted(): void
    {
        static::creating(function (AcaoDespesas $row) {
            $row->atualizado_por = Auth::user()->id;
        });

        // static::created(function (AcaoDespesas $row){
        // });

        static::updating(function (AcaoDespesas $row) {
            $row->atualizado_por = Auth::user()->id;
        });

        // static::updated(function (AcaoDespesas $row){
        // });

        // static::deleted(function (AcaoDespesas $row){
        // });
    }
}
