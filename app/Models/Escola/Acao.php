<?php

namespace App\Models\Escola;

use App\Models\Administrativo\Agenda;
use App\Models\Escola\Cursos;
use App\Models\Administrativo\Demanda;
use App\Models\User;
use App\Services\AgendaService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

class Acao extends Model
{
    protected $table = 'esc_acao';
    protected $fillable = [
        'demanda_id',
        'tipoatividade_id',
        'atualizado_por',
        'codigo',
        'ano',
        'descricao',
        'metadados',
        'data_inicio',
        'data_termino',
        'local_atividade',
        'carga_horaria',
        'numero_vagas',
        'informacao',
        'objetivo',
        'ementa',
        'criterio_selecao',
        'observacao',
    ];

    protected function casts(): array
    {
        return [
            'metadados' => 'array',
            'data_inicio' => 'date',
            'data_termino' => 'date',
        ];
    }

    public function demanda(): BelongsTo
    {
        return $this->belongsTo(Demanda::class, 'demanda_id', 'id');
    }

    public function agendas(): MorphMany
    {
        return $this->morphMany(Agenda::class, 'agendaable');
    }

    public function temAtividades(): bool
    {
        return false;
    }

    public function atualizado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }
    public function tipoatividade(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tipoatividade_id', 'id');
    }
    public function cursos(): HasMany
    {
        return $this->hasMany(Cursos::class, 'acao_id', 'id');
    }
    public function despesas(): HasMany
    {
        return $this->hasMany(AcaoDespesas::class, 'acao_id', 'id');
    }
    protected static function booted(): void
    {
        static::creating(function (Acao $row) {
            $row->atualizado_por = Auth::user()->id;
        });

        // static::created(function (Acao $row){
        // });

        static::updating(function (Acao $row) {
            $row->atualizado_por = Auth::user()->id;
        });

        static::saved(function (Acao $row) {
            AgendaService::sincronizarAgendaInformativa($row);
        });

        static::deleting(function (Acao $row) {
            $row->agendas()->delete();
        });
    }
}
