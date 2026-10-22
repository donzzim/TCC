<?php

namespace App\Models\Administrativo;

use App\Models\Escola\Cursos;
use App\Models\Tags;
use App\Models\Traits\HasSetorScope;
use App\Models\Traits\RecordsUserDataAndSetor;
use App\Models\User;
use Carbon\CarbonInterface;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * @property string|null $nome_instrutor
 * @property \Carbon\Carbon|null $data_prevista
 * @property-read Cards|null $card
 */
class Demanda extends Model
{
    use HasSetorScope, RecordsUserDataAndSetor;

    protected $table = 'adm_demandas';

    protected $fillable = [
        'list_id',
        'card_id',
        'tipodemanda_id',
        'indicado_por',
        'atualizado_por',
        'titulo',
        'descricao',
        'nome_instrutor',
        'data_prevista',
        'data_termino',
        'qtde_horas',
        'numero_participantes',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'data_prevista' => 'datetime',
            'data_termino' => 'datetime',
        ];
    }

    public function getInicio(): CarbonInterface
    {
        return $this->data_prevista
            ? \Carbon\Carbon::parse($this->data_prevista)
            : \Carbon\Carbon::now();
    }

    public function getTermino(): CarbonInterface
    {
        return $this->data_termino
            ? \Carbon\Carbon::parse($this->data_termino)
            : \Carbon\Carbon::now();
    }

    public function agendas(): MorphMany
    {
        return $this->morphMany(Agenda::class, 'agendaable');
    }

    public function list(): BelongsTo
    {
        return $this->belongsTo(Lists::class, 'list_id', 'id');
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Cards::class, 'card_id', 'id');
    }

    public function tipodemanda(): BelongsTo
    {
        return $this->belongsTo(TipoDemandas::class, 'tipodemanda_id', 'id');
    }

    public function indicado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'indicado_por', 'id');
    }

    public function atualizado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por', 'id');
    }

    // public function tags(): BelongsToMany
    // {
    //     return $this->belongsToMany(Tags::class, 'demanda_tags','demanda_id','tag_id');
    // }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(
            Tags::class,
            'taggable',
            'taggables',
            'taggable_id',
            'tag_id'
        );
    }

    public function cursos(): HasMany
    {
        return $this->hasMany(Cursos::class, 'demanda_id', 'id');
    }

    public function tarefas(): HasMany
    {
        return $this->hasMany(Tarefas::class, 'demanda_id', 'id');
    }

    public function registros(): HasMany
    {
        return $this->hasMany(RegistroFuncional::class, 'demanda_id', 'id');
    }

    protected static function booted(): void
    {
        parent::booted();
        static::bootRecordsUserDataAndSetor();

        static::creating(function (Demanda $row) {
            $row->list_id = 1;
            $row->card_id = 1;
            $row->ativo = 1;
            $row->atualizado_por = Auth::id();
        });

        static::created(function (Demanda $row) {
            // copiar todas os registros de tasks para tarefas da demanda
            $tasks = Tasks::all();
            foreach ($tasks as $task) {
                $nova = new Tarefas;
                $nova->demanda_id = $row->id;
                $nova->list_id = $task->card->list_id;
                $nova->card_id = $task->card_id;
                $nova->task_id = $task->id;
                $nova->descricao = $task->descricao;
                $nova->cor = $task->cor;
                $nova->save();
            }

            if ($user = Auth::user()) {
                Notification::make()
                    ->title('Demandas')
                    ->body('Demanda registrada corretamente!')
                    ->sendToDatabase($user);
            }
        });

        static::updating(function (Demanda $row) {
            Log::info("Atualizando demanda {$row->id} - {$row->titulo}");
            $row->atualizado_por = Auth::id();
        });

        static::updated(function (Demanda $row) {
            if ($user = Auth::user()) {
                Notification::make()
                    ->title('Demandas')
                    ->body('Demanda atualizada corretamente!')
                    ->success()
                    ->sendToDatabase($user);
            }
        });

        // static::deleted(function (Demanda $row){
        // });
    }
}
