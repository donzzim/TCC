<?php

namespace App\Models\egap\Cadastro;

use App\Filament\Egap\Auth\EgapSession;
use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fornecedores extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_mat_fornecedor';
    public $timestamps = false;
    protected $fillable = [
        'date_time',
        'NomeFornecedor',
        'Pessoa',
        'CNPJ',
        'Usuario',
        'telefone_comercial',
        'telefone_celular',
    ];

    protected $casts = [
        'date_time' => 'datetime',
    ];

    public function atualizado_por(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'Usuario', 'id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
            $model->Usuario = EgapSession::resolverAtual() ?? 111;
        });
    }
}
