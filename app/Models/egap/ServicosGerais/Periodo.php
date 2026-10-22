<?php

namespace App\Models\egap\ServicosGerais;

use App\Models\egap\UserEgap;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Egap\Auth\EgapSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Período de avaliação de um {@see Contrato} (legado: ava_periodo).
 *
 * `nota_1`/`nota_2`/`nota_3` substituem `ng1`/`ng2`/`ng3`: o significado de
 * cada um depende do tipo do contrato — ver
 * {@see \App\Filament\Egap\Externo\Groups\ServicosGerais\Enums\TipoContrato::rotulosNotasPeriodo()}.
 *
 * Assim como no legado (telas "editarperiodo" vs "editarnota"), depois que
 * {@see formularios()} existir para o período as datas/nome não devem mais
 * ser editados — mas aqui isso é resolvido desabilitando os campos no mesmo
 * formulário ({@see temFormularios()}), em vez de duas telas separadas.
 */
class Periodo extends Model
{
    protected $connection = 'emes';

    protected $table = 'egap_avc_periodos';

    public $timestamps = false;

    protected $fillable = [
        'contrato_id',
        'nome',
        'inicio',
        'fim',
        'nota_1',
        'nota_2',
        'nota_3',
        'date_time',
        'usuario_id',
    ];

    protected $casts = [
        'inicio' => 'date',
        'fim' => 'date',
        'nota_1' => 'integer',
        'nota_2' => 'integer',
        'nota_3' => 'integer',
        'date_time' => 'datetime',
    ];

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class);
    }

    public function formularios(): HasMany
    {
        return $this->hasMany(Formulario::class);
    }

    public function atualizadoPor(): BelongsTo
    {
        return $this->belongsTo(UserEgap::class, 'usuario_id');
    }

    public function temFormularios(): bool
    {
        return $this->formularios()->exists();
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->date_time = now();
        });
    }
}
