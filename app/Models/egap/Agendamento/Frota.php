<?php

namespace App\Models\egap\Agendamento;

use App\Filament\Egap\Auth\EgapSession;
use App\Models\egap\Cadastro\Marcas;
use App\Models\egap\Cadastro\Modelos;
use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Frota extends Model
{
    protected $connection = 'emes';
    protected $table = 'egap_age_frota';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'date_time',
        'id_user',
        'descricao',
        'marca',
        'modelo',
        'placa',
        'proprietario',
        'disponivel',
        'ativo',
    ];

    protected $casts = [
        'date_time' => 'datetime',
        'disponivel' => 'boolean',
        'ativo' => 'boolean',
    ];

    public function marcaRef(): BelongsTo
    {
        return $this->belongsTo(Marcas::class, 'marca', 'id');
    }
    public function modeloRef(): BelongsTo
    {
        return $this->belongsTo(Modelos::class, 'modelo', 'id');
    }

    public function idUserRef(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'id_user', 'id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
            $model->id_user = EgapSession::resolverAtual() ?? 111;
        });
    }
}
