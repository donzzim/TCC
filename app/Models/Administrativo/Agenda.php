<?php

namespace App\Models\Administrativo;

use App\Contracts\AgendavelInterface;
use App\Models\Escola\CursoFrequencias;
use App\Models\Escola\Cursos;
use App\Models\Escola\TipoLocal;
use App\Models\Traits\HasSetorScope;
use App\Models\Traits\RecordsUserDataAndSetor;
use App\Models\User;
use App\Services\Pedagogico\FrequenciaService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class Agenda extends Model
{
    use HasSetorScope, RecordsUserDataAndSetor;

    protected $table = 'adm_agenda';
    protected $fillable = [
        'setor_id',
        'curso_id',
        'atualizado_por',
        'titulo',
        'descricao',
        'inicio',
        'termino',
        'dia_todo',
        'cor_fundo',
        'cor_texto',
        'agendaable_type',
        'agendaable_id',
        'local_id',
    ];

    protected $casts = [
        'inicio'  => 'datetime',
        'termino' => 'datetime',
    ];


    public function agendaable(): MorphTo
    {
        return $this->morphTo();
    }
    public function local(): BelongsTo
    {
        return $this->belongsTo(TipoLocal::class, 'local_id', 'id');
    }

    public function atualizado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }

    public function frequencias(): HasMany
    {
        return $this->hasMany(CursoFrequencias::class, 'agenda_id', 'id');
    }
    public function atualizarFrequencias(): void
    {
        FrequenciaService::sincronizarFrequenciasDaAgenda($this);
    }

    public static function booted(): void
    {
        parent::booted();
        static::bootRecordsUserDataAndSetor();

        static::updating(function (Agenda $a) {
            Log::info("Agenda {$a->id} está prestes a ser atualizada. Alterações: " . json_encode($a->getDirty()));
        });

        static::created(function (Agenda $a) {
            if ($a->agendaable) {
                FrequenciaService::sincronizarFrequenciasDaAgenda($a);
            }
        });

        static::updated(function (Agenda $a) {
            $changes = $a->getChanges();

            if (array_intersect(['agendaable_id', 'agendaable_type'], array_keys($changes))) {
                FrequenciaService::reconciliarFrequencias($a);
                return;
            }
            if (array_intersect(['inicio', 'termino'], array_keys($changes))) {
                $a->atualizarFrequencias();
            }
        });

        static::deleting(function (Agenda $a) {
            if ($a->frequencias()->where('situacaofrequencia_id', config('settings.frequencia.presente'))->exists()) {
                Log::info('Agenda com frequencias presentes vinculadas, ignorando a delecao');
                return false;
            }
            $a->frequencias()->delete();
        });
    }
}
